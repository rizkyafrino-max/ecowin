<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\PerangkatPush;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\PengumumanService;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Token perangkat FCM dan pengiriman "ping" tanpa isi. */
class PushFcmTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private Nasabah $a;

    private Nasabah $b;

    private string $berkasKunci;

    private $kunciPublik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bank = BankSampah::factory()->create();
        $this->a = Nasabah::factory()->forBank($this->bank)->create();
        $this->b = Nasabah::factory()->forBank($this->bank)->create();
        PushService::lupakan();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        config(['services.firebase.credentials' => null]);
        PushService::lupakan();
        if (isset($this->berkasKunci) && is_file($this->berkasKunci)) {
            @unlink($this->berkasKunci);
        }
        parent::tearDown();
    }

    /** Membuat kredensial akun layanan palsu dengan kunci RSA sungguhan agar tanda tangan JWT bisa diverifikasi. */
    private function siapkanFirebase(string $tokenUri = 'https://oauth2.googleapis.com/token'): void
    {
        $kunci = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($kunci, $privat);
        $this->kunciPublik = openssl_pkey_get_details($kunci)['key'];
        $this->berkasKunci = sys_get_temp_dir().'/ecowin-fcm-'.uniqid().'.json';
        file_put_contents($this->berkasKunci, json_encode([
            'type' => 'service_account', 'project_id' => 'ecowin-uji', 'client_email' => 'fcm@ecowin-uji.iam.gserviceaccount.com',
            'private_key' => $privat, 'token_uri' => $tokenUri,
        ]));
        config(['services.firebase.credentials' => $this->berkasKunci]);
        PushService::lupakan();
    }

    private function token(string $s = 'A'): string
    {
        return str_repeat($s, 40).':APA91b'.str_repeat('x', 100);
    }

    public function test_nasabah_mendaftarkan_dan_melepas_perangkat_sendiri(): void
    {
        $t = $this->token();
        $this->actingAsApi($this->a->user)->postJson('/api/perangkat', ['token' => $t])->assertCreated();
        $this->assertDatabaseCount('perangkat_push', 1);

        // token tersimpan terenkripsi, bukan teks asli
        $mentah = \DB::table('perangkat_push')->first();
        $this->assertStringNotContainsString($t, $mentah->token);
        $this->assertSame($t, PerangkatPush::query()->first()->token);

        // akun lain tidak bisa melepas token orang lain
        $this->actingAsApi($this->b->user)->deleteJson('/api/perangkat', ['token' => $t])->assertOk();
        $this->assertDatabaseCount('perangkat_push', 1);

        $this->actingAsApi($this->a->user)->deleteJson('/api/perangkat', ['token' => $t])->assertOk();
        $this->assertDatabaseCount('perangkat_push', 0);
    }

    public function test_perangkat_yang_sama_berpindah_ke_akun_yang_login_terakhir(): void
    {
        $t = $this->token();
        $this->actingAsApi($this->a->user)->postJson('/api/perangkat', ['token' => $t])->assertCreated();
        $this->actingAsApi($this->b->user)->postJson('/api/perangkat', ['token' => $t])->assertCreated();

        $this->assertDatabaseCount('perangkat_push', 1);
        $this->assertSame($this->b->user_id, PerangkatPush::query()->first()->user_id);
    }

    public function test_validasi_otorisasi_dan_batas_perangkat(): void
    {
        $this->postJson('/api/perangkat', ['token' => $this->token()])->assertUnauthorized();
        $api = $this->actingAsApi($this->a->user);
        $api->postJson('/api/perangkat', ['token' => 'pendek'])->assertUnprocessable();
        $api->postJson('/api/perangkat', ['token' => 'ada spasi '.str_repeat('x', 30)])->assertUnprocessable();
        $api->postJson('/api/perangkat', ['token' => $this->token(), 'platform' => 'ios'])->assertUnprocessable();

        // petugas bukan nasabah: tidak punya akses
        $petugas = User::factory()->petugas($this->bank)->create();
        $this->actingAsApi($petugas)->postJson('/api/perangkat', ['token' => $this->token()])->assertForbidden();

        foreach (range(1, 7) as $i) {
            $this->actingAsApi($this->a->user)->postJson('/api/perangkat', ['token' => $this->token((string) $i)])->assertCreated();
        }
        $this->assertSame(5, PerangkatPush::query()->where('user_id', $this->a->user_id)->count());
    }

    public function test_ping_hanya_berisi_tipe_tanpa_isi_notifikasi_dan_ditandatangani_dengan_benar(): void
    {
        $this->siapkanFirebase();
        PerangkatPush::query()->forceCreate(['user_id' => $this->a->user_id, 'token_hash' => PerangkatPush::hash($this->token()), 'token' => $this->token(), 'platform' => 'android']);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'at-uji', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok']),
        ]);

        app(NotifikasiService::class)->kirim($this->a->user, 'penarikan_disetujui', 'Penarikan disetujui', 'Penarikan Rp 500.000 disetujui.', 'saldo');

        Http::assertSent(function (HttpRequest $r): bool {
            if (! str_contains($r->url(), 'fcm.googleapis.com/v1/projects/ecowin-uji/messages:send')) {
                return false;
            }
            $badan = json_encode($r->data());
            // tidak ada judul/isi/nominal di FCM; hanya penanda "kabar"
            return $r->hasHeader('Authorization', 'Bearer at-uji')
                && ($r->data()['message']['data'] ?? null) === ['tipe' => 'kabar']
                && ! str_contains($badan, 'Penarikan') && ! str_contains($badan, '500.000')
                && ! isset($r->data()['message']['notification']);
        });

        // JWT yang dikirim ke server token Google bertanda tangan sah, untuk lingkup firebase.messaging
        Http::assertSent(function (HttpRequest $r): bool {
            if (! str_contains($r->url(), 'oauth2.googleapis.com/token')) {
                return false;
            }
            [$h, $c, $s] = explode('.', $r->data()['assertion']);
            $d = fn (string $x) => base64_decode(strtr($x, '-_', '+/'));
            $klaim = json_decode($d($c), true);

            return openssl_verify("$h.$c", $d($s), $this->kunciPublik, OPENSSL_ALGO_SHA256) === 1
                && $klaim['scope'] === 'https://www.googleapis.com/auth/firebase.messaging'
                && $klaim['iss'] === 'fcm@ecowin-uji.iam.gserviceaccount.com';
        });
    }

    public function test_tanpa_kredensial_tidak_ada_panggilan_jaringan(): void
    {
        Http::fake();
        PerangkatPush::query()->forceCreate(['user_id' => $this->a->user_id, 'token_hash' => PerangkatPush::hash($this->token()), 'token' => $this->token(), 'platform' => 'android']);

        app(NotifikasiService::class)->kirim($this->a->user, 'setoran_anorganik', 'Setoran tercatat', 'Isi', 'transaksi');

        Http::assertNothingSent();
        $this->assertFalse(app(PushService::class)->tersedia());
    }

    public function test_kredensial_dengan_alamat_token_selain_google_ditolak(): void
    {
        $this->siapkanFirebase('https://penyerang.example.com/token');
        Http::fake();
        PerangkatPush::query()->forceCreate(['user_id' => $this->a->user_id, 'token_hash' => PerangkatPush::hash($this->token()), 'token' => $this->token(), 'platform' => 'android']);

        app(PushService::class)->bangunkan([$this->a->user_id]);

        Http::assertNothingSent(); // JWT bertanda tangan tidak boleh dikirim ke alamat lain
        $this->assertFalse(app(PushService::class)->tersedia());
    }

    public function test_token_tidak_berlaku_dihapus_dan_akun_nonaktif_tidak_dibangunkan(): void
    {
        $this->siapkanFirebase();
        $mati = PerangkatPush::query()->forceCreate(['user_id' => $this->a->user_id, 'token_hash' => PerangkatPush::hash($this->token('M')), 'token' => $this->token('M'), 'platform' => 'android']);
        $nonaktif = PerangkatPush::query()->forceCreate(['user_id' => $this->b->user_id, 'token_hash' => PerangkatPush::hash($this->token('N')), 'token' => $this->token('N'), 'platform' => 'android']);
        $this->b->user->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'at', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'UNREGISTERED']], 404),
        ]);

        app(PushService::class)->bangunkan([$this->a->user_id, $this->b->user_id]);

        $this->assertDatabaseMissing('perangkat_push', ['id' => $mati->id]);   // token mati dibersihkan
        $this->assertDatabaseHas('perangkat_push', ['id' => $nonaktif->id]);    // tidak dikirimi, tidak disentuh
        Http::assertSentCount(2); // 1 token akses + 1 pesan (hanya akun aktif)
    }

    public function test_pengumuman_membangunkan_semua_penerima(): void
    {
        $this->siapkanFirebase();
        foreach ([$this->a, $this->b] as $i => $n) {
            PerangkatPush::query()->forceCreate(['user_id' => $n->user_id, 'token_hash' => PerangkatPush::hash($this->token((string) $i)), 'token' => $this->token((string) $i), 'platform' => 'android']);
        }
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'at', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok']),
        ]);

        $admin = User::factory()->admin()->create();
        $p = new \App\Models\Pengumuman;
        $p->forceFill(['dibuat_oleh' => $admin->id, 'bank_sampah_id' => $this->bank->id, 'judul' => 'Jadwal', 'isi' => 'Hari ini 08.00', 'kirim_pada' => now()])->save();
        app(PengumumanService::class)->kirim($p->id);

        Http::assertSentCount(3); // 1 token akses + 2 ping
    }
}
