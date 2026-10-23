<?php

namespace App\Services;

use App\Models\PerangkatPush;
use App\Models\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mengirim "ping" FCM (HTTP v1) yang membangunkan aplikasi Android nasabah agar segera mengambil notifikasi baru.
 *
 * Keamanan:
 *  - Pesan FCM hanya berisi {tipe: "kabar"}; judul, isi, dan nominal TIDAK pernah lewat server Google. Aplikasi mengambil
 *    isi dari API EcoWin yang terautentikasi, jadi layar kunci dan Google tidak melihat data sensitif.
 *  - Berkas kredensial layanan hanya dibaca dari disk server (FIREBASE_CREDENTIALS), tidak pernah ke repository/klien.
 *  - Tanpa kredensial layanan ini tidak melakukan apa pun (aplikasi tetap memakai pengecekan berkala).
 *  - Kegagalan push tidak boleh menggagalkan proses utama; token tidak pernah dicatat di log.
 */
class PushService
{
    private const SKOP = 'https://www.googleapis.com/auth/firebase.messaging';

    private const HOST_TOKEN = 'oauth2.googleapis.com';

    private const CACHE_TOKEN = 'firebase.access_token';

    /** @var array<string, mixed>|null|false */
    private static array|null|false $kredensial = false;

    public function tersedia(): bool
    {
        return $this->kredensial() !== null;
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function bangunkan(array $userIds): void
    {
        if ($userIds === [] || ! $this->tersedia()) {
            return;
        }

        try {
            $perangkat = PerangkatPush::query()
                ->join('users', 'users.id', '=', 'perangkat_push.user_id')
                ->whereIn('perangkat_push.user_id', array_unique($userIds))
                ->where('users.status', User::STATUS_AKTIF)
                ->whereNull('users.deleted_at')
                ->select('perangkat_push.*')
                ->get();

            foreach ($perangkat->chunk(50) as $kelompok) {
                $this->kirimKelompok($kelompok->values()->all());
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<int, PerangkatPush>  $kelompok
     */
    private function kirimKelompok(array $kelompok, bool $ulang = true): void
    {
        $kredensial = $this->kredensial();
        $at = $this->tokenAkses();
        if ($at === null || $kredensial === null) {
            return;
        }
        $url = 'https://fcm.googleapis.com/v1/projects/'.rawurlencode($kredensial['project_id']).'/messages:send';

        /** @var array<string, Response> $hasil */
        $hasil = Http::pool(function (Pool $pool) use ($kelompok, $url, $at): array {
            $reqs = [];
            foreach ($kelompok as $p) {
                $reqs[] = $pool->as((string) $p->id)->withToken($at)->acceptJson()->connectTimeout(3)->timeout(8)->post($url, [
                    'message' => [
                        'token' => $p->token,
                        'data' => ['tipe' => 'kabar'],
                        'android' => ['priority' => 'HIGH', 'ttl' => '3600s', 'collapse_key' => 'kabar'],
                    ],
                ]);
            }

            return $reqs;
        });

        $perluUlang = [];
        foreach ($kelompok as $p) {
            $r = $hasil[(string) $p->id] ?? null;
            if (! $r instanceof Response) {
                continue; // galat jaringan: abaikan, pengecekan berkala menjadi cadangan
            }
            if ($r->successful()) {
                continue;
            }
            if ($r->status() === 401 && $ulang) {
                $perluUlang[] = $p;

                continue;
            }
            $status = (string) $r->json('error.status', '');
            if ($r->status() === 404 || in_array($status, ['NOT_FOUND', 'UNREGISTERED'], true)
                || ($r->status() === 400 && $status === 'INVALID_ARGUMENT')) {
                $p->delete(); // token tidak berlaku lagi (aplikasi dihapus/dibersihkan)

                continue;
            }
            report(new \RuntimeException('FCM gagal: HTTP '.$r->status().' '.$status)); // tanpa token/isi
        }

        if ($perluUlang !== []) {
            Cache::forget(self::CACHE_TOKEN); // token akses kedaluwarsa: ambil baru lalu coba sekali lagi
            $this->kirimKelompok($perluUlang, false);
        }
    }

    private function tokenAkses(): ?string
    {
        if ($ada = Cache::get(self::CACHE_TOKEN)) {
            return (string) $ada;
        }
        $k = $this->kredensial();
        if ($k === null) {
            return null;
        }

        $b64 = fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $sekarang = time();
        $badan = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode([
            'iss' => $k['client_email'],
            'scope' => self::SKOP,
            'aud' => $k['token_uri'],
            'iat' => $sekarang,
            'exp' => $sekarang + 3600,
        ]));
        if (! openssl_sign($badan, $tanda, $k['private_key'], OPENSSL_ALGO_SHA256)) {
            report(new \RuntimeException('Tanda tangan JWT Firebase gagal.'));

            return null;
        }

        $r = Http::asForm()->connectTimeout(3)->timeout(8)->post($k['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $badan.'.'.$b64($tanda),
        ]);
        if (! $r->successful() || ! $r->json('access_token')) {
            report(new \RuntimeException('Token akses Firebase gagal: HTTP '.$r->status()));

            return null;
        }
        $token = (string) $r->json('access_token');
        Cache::put(self::CACHE_TOKEN, $token, max(60, (int) $r->json('expires_in', 3600) - 300));

        return $token;
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string, token_uri: string}|null
     */
    private function kredensial(): ?array
    {
        if (self::$kredensial !== false) {
            return self::$kredensial;
        }
        $path = (string) config('services.firebase.credentials');
        $hasil = null;
        if ($path !== '') {
            $penuh = (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1) ? $path : base_path($path);
            if (is_file($penuh)) {
                $j = json_decode((string) file_get_contents($penuh), true);
                $lengkap = is_array($j) && ($j['type'] ?? '') === 'service_account';
                foreach (['project_id', 'client_email', 'private_key', 'token_uri'] as $k) {
                    $lengkap = $lengkap && ! empty($j[$k]) && is_string($j[$k]);
                }
                // Jangan pernah mengirim JWT bertanda tangan ke alamat selain server token Google.
                if ($lengkap && parse_url($j['token_uri'], PHP_URL_HOST) === self::HOST_TOKEN && str_starts_with($j['token_uri'], 'https://')) {
                    $hasil = $j;
                }
            }
        }

        return self::$kredensial = $hasil;
    }

    /** Untuk tes: lupakan kredensial yang sudah dibaca. */
    public static function lupakan(): void
    {
        self::$kredensial = false;
        Cache::forget(self::CACHE_TOKEN);
    }
}
