<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use App\Services\BioporiService;
use App\Services\NasabahService;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Nasabah yang mendaftar sendiri berstatus "pending" sampai petugas memverifikasinya. Selama itu mereka tidak boleh bertransaksi
 * (organik maupun anorganik), dan tidak boleh melihat data layanan: ditolak di server (403, code "belum_diverifikasi"),
 * bukan hanya disembunyikan di tampilan. Profil, notifikasi, lapor bug, hapus akun, dan keluar tetap terbuka.
 */
class NasabahBelumDiverifikasiTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private User $petugas;

    private Nasabah $pending;

    private Nasabah $terverifikasi;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->pending = Nasabah::factory()->forBank($this->bank)->create();
        $this->pending->forceFill(['status_verifikasi' => 'pending'])->save();
        $this->terverifikasi = Nasabah::factory()->forBank($this->bank)->create();
        $this->jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 2000]);
    }

    public function test_unverified_nasabah_is_blocked_from_every_data_route(): void
    {
        $this->actingAsApi($this->pending->user);

        $baca = [
            '/api/dashboard', '/api/dashboard/summary', '/api/dashboard/statistics',
            '/api/harga', '/api/saldo', '/api/saldo/history', '/api/qr', '/api/transaksi',
            '/api/me/saldo', '/api/me/mutasi-saldo', '/api/me/qr',
            '/api/transaksi/anorganik', '/api/organik', '/api/organik/biopori', '/api/organik/biopori/lokasi',
            '/api/biopori', '/api/penarikan',
        ];
        foreach ($baca as $url) {
            $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'belum_diverifikasi');
        }

        $tulis = [
            '/api/penarikan' => ['jumlah' => 20000],
            '/api/organik/biopori' => ['berat_kg' => 1],
            '/api/organik/biopori/lokasi' => ['nama_lokasi' => 'Pojok Taman', 'latitude' => -7.25, 'longitude' => 112.75],
            '/api/biopori' => ['berat_kg' => 1],
        ];
        foreach ($tulis as $url => $isi) {
            $this->postJson($url, $isi)->assertForbidden()->assertJsonPath('code', 'belum_diverifikasi');
        }
    }

    public function test_unverified_nasabah_can_still_use_profile_notifications_report_bug_and_delete_account(): void
    {
        $this->actingAsApi($this->pending->user);

        $this->getJson('/api/auth/me')->assertOk();
        $this->getJson('/api/profile')->assertOk();
        $this->getJson('/api/notifikasi')->assertOk();
        $this->getJson('/api/notifikasi/jumlah')->assertOk();
        // Hapus akun tidak diblokir kunci verifikasi (di sini hanya kata konfirmasi yang kurang, bukan 403).
        $this->postJson('/api/me/akun/hapus')->assertStatus(422)->assertJsonValidationErrors('konfirmasi');
        $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Halaman tidak bisa dibuka setelah saya mendaftar.'])
            ->assertStatus(201);
    }

    public function test_verified_nasabah_and_staff_are_not_blocked(): void
    {
        $this->actingAsApi($this->terverifikasi->user);
        foreach (['/api/dashboard/summary', '/api/saldo', '/api/harga', '/api/qr', '/api/organik/biopori/lokasi', '/api/penarikan'] as $url) {
            $this->getJson($url)->assertOk();
        }

        $this->actingAsApi($this->petugas);
        $this->getJson('/api/organik')->assertOk();
        $this->getJson('/api/transaksi/anorganik')->assertOk();
    }

    public function test_verification_by_petugas_unlocks_the_routes_immediately(): void
    {
        $this->actingAsApi($this->pending->user)->getJson('/api/dashboard/summary')->assertForbidden();

        app(NasabahService::class)->verifikasi($this->petugas, $this->pending->fresh());

        $this->actingAsApi($this->pending->fresh()->user);
        $this->getJson('/api/dashboard/summary')->assertOk();
        $this->getJson('/api/saldo')->assertOk();
    }

    public function test_petugas_cannot_record_transactions_for_unverified_nasabah(): void
    {
        $this->actingAsApi($this->petugas);

        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->pending->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 3])
            ->assertStatus(422)->assertJsonValidationErrors('nasabah_id');
        $this->postJson('/api/organik', [
            'nasabah_id' => $this->pending->id, 'jenis_organik' => 'Sisa sayur', 'berat_kg' => 2,
            'checklist_bebas_plastik' => true, 'checklist_bebas_logam' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('nasabah_id');

        $this->assertSame(0, TransaksiAnorganik::query()->count());
        $this->assertSame(0, TransaksiOrganik::query()->count());
        $this->assertSame(0.0, (float) $this->pending->fresh()->saldo);

        // Setelah diverifikasi, pencatatan berjalan normal.
        app(NasabahService::class)->verifikasi($this->petugas, $this->pending->fresh());
        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->pending->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 3])->assertCreated();
        $this->assertSame(6000.0, (float) $this->pending->fresh()->saldo);
    }

    public function test_services_refuse_unverified_nasabah_even_without_the_http_layer(): void
    {
        $pending = $this->pending->fresh();

        try {
            app(TransaksiService::class)->catatAnorganik($this->petugas, $pending, $this->jenis, 2);
            $this->fail('Setoran anorganik untuk nasabah belum diverifikasi seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('nasabah_id', $e->errors());
        }

        try {
            app(TransaksiService::class)->catatOrganik($this->petugas, $pending, ['jenis_organik' => 'Daun kering', 'berat_kg' => 1]);
            $this->fail('Setoran organik untuk nasabah belum diverifikasi seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('nasabah_id', $e->errors());
        }

        try {
            app(BioporiService::class)->lapor($pending->user, [
                'titik_biopori_id' => 1, 'tanggal_pemasukan' => now()->toDateString(), 'jenis_sampah' => 'Sisa sayur', 'berat_kg' => 1,
            ], UploadedFile::fake()->image('bukti.jpg'));
            $this->fail('Lapor aktivitas Biopori oleh nasabah belum diverifikasi seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum diverifikasi', $e->errors()['titik_biopori_id'][0]);
        }

        $this->assertSame(0, TransaksiAnorganik::query()->count() + TransaksiOrganik::query()->count());
    }
}
