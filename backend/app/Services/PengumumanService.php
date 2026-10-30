<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mengirim pengumuman sebagai notifikasi dalam aplikasi ke nasabah aktif. Aman dipanggil berulang (idempotent):
 * pengumuman dikunci dan ditandai terkirim dalam satu transaksi, jadi tidak pernah terkirim dua kali.
 */
class PengumumanService
{
    /** Batas pembuatan per pengguna per 24 jam, agar akun yang disalahgunakan tidak bisa membanjiri nasabah. */
    public const BATAS_HARIAN = 10;

    public function melebihiBatas(User $pembuat): bool
    {
        return Pengumuman::query()->where('dibuat_oleh', $pembuat->id)->where('created_at', '>=', now()->subDay())->count() >= self::BATAS_HARIAN;
    }

    /** Jumlah nasabah aktif yang akan menerima pengumuman untuk bank sampah tertentu (null = semua). */
    public function hitungPenerima(?int $bankSampahId): int
    {
        return $this->penerima($bankSampahId)->count();
    }

    /** Kirim semua pengumuman yang sudah jatuh waktu. Mengembalikan jumlah pengumuman yang dikirim. */
    public function kirimYangJatuhTempo(): int
    {
        $dikirim = 0;
        Pengumuman::query()->whereNull('terkirim_at')->where('kirim_pada', '<=', now())->orderBy('id')->pluck('id')->each(function (int $id) use (&$dikirim): void {
            if ($this->kirim($id)) {
                $dikirim++;
            }
        });

        return $dikirim;
    }

    public function kirim(int $id): bool
    {
        $ids = [];
        $terkirim = DB::transaction(function () use ($id, &$ids): bool {
            $p = Pengumuman::query()->whereKey($id)->lockForUpdate()->first();
            if ($p === null || $p->sudahTerkirim()) {
                return false;
            }

            $sekarang = now();
            $jumlah = 0;
            $this->penerima($p->bank_sampah_id)->select('users.id')->orderBy('users.id')->chunk(500, function ($baris) use ($p, $sekarang, &$jumlah, &$ids): void {
                foreach ($baris as $u) {
                    $ids[] = (int) $u->id;
                }
                $data = $baris->map(fn ($u): array => [
                    'user_id' => $u->id,
                    'tipe' => 'pengumuman',
                    'judul' => Str::limit($p->judul, 120, ''),
                    'isi' => Str::limit($p->isi, 300, '…'),
                    'aksi' => 'beranda',
                    'created_at' => $sekarang,
                ])->all();
                DB::table('notifikasi')->insert($data);
                $jumlah += count($data);
            });

            $p->forceFill(['terkirim_at' => $sekarang, 'jumlah_penerima' => $jumlah])->save();
            app(AuditLogger::class)->log('kirim_pengumuman', $p, null, ['judul' => $p->judul, 'bank_sampah_id' => $p->bank_sampah_id, 'jumlah_penerima' => $jumlah], $p->pembuat, null);

            return true;
        });

        // Bangunkan perangkat setelah transaksi selesai agar kunci baris tidak tertahan oleh panggilan jaringan.
        if ($terkirim && $ids !== []) {
            app(PushService::class)->bangunkan($ids);
        }

        return $terkirim;
    }

    /** Nasabah berstatus aktif (akun dan profil), tidak dihapus, pada bank sampah tujuan. */
    private function penerima(?int $bankSampahId)
    {
        return User::query()
            ->join('nasabah', 'nasabah.user_id', '=', 'users.id')
            ->where('users.status', User::STATUS_AKTIF)
            ->where('nasabah.status', 'aktif')
            ->whereNull('nasabah.deleted_at')
            ->when($bankSampahId !== null, fn ($q) => $q->where('nasabah.bank_sampah_id', $bankSampahId));
    }
}
