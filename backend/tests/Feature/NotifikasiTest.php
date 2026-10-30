<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\TitikBiopori;
use App\Models\User;
use App\Services\NasabahService;
use App\Services\PenarikanService;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Notifikasi dalam aplikasi untuk nasabah: dibuat pada kejadian penting, hanya terlihat oleh pemiliknya. */
class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    private BankSampah $bank;

    private Nasabah $nasabah;

    private Nasabah $lain;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create();
        $this->lain = Nasabah::factory()->forBank($this->bank)->create();
        $this->jenis = JenisSampah::factory()->create(['nama_jenis' => 'Kardus']);
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 2000]);
    }

    private function tipe(User $u): array
    {
        return Notifikasi::query()->where('user_id', $u->id)->orderBy('id')->pluck('tipe')->all();
    }

    public function test_deposit_notifications_go_only_to_the_owner(): void
    {
        $svc = app(TransaksiService::class);
        $svc->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 3);
        $svc->catatOrganik($this->petugas, $this->nasabah, ['jenis_organik' => 'Sisa sayur', 'berat_kg' => 2]);

        $this->assertSame(['setoran_anorganik', 'setoran_organik'], $this->tipe($this->nasabah->user));
        $this->assertSame([], $this->tipe($this->lain->user));
        $this->assertSame([], $this->tipe($this->petugas));

        $n = Notifikasi::query()->where('tipe', 'setoran_anorganik')->first();
        $this->assertStringContainsString('Rp 6.000', $n->isi);
        $this->assertStringContainsString('Kardus', $n->isi);
    }

    public function test_withdrawal_decisions_notify(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 10);
        $penarikan = app(PenarikanService::class);

        $a = $penarikan->ajukan($this->nasabah->user, $this->nasabah, 5000);
        $penarikan->setujui($this->petugas, $a);
        $penarikan->selesaikan($this->petugas, $a->fresh());
        $b = $penarikan->ajukan($this->nasabah->user, $this->nasabah, 3000);
        $penarikan->tolak($this->petugas, $b, 'Data belum lengkap');

        $tipe = $this->tipe($this->nasabah->user);
        $this->assertContains('penarikan_disetujui', $tipe);
        $this->assertContains('penarikan_selesai', $tipe);
        $this->assertContains('penarikan_ditolak', $tipe);
        $this->assertStringContainsString('Data belum lengkap', Notifikasi::query()->where('tipe', 'penarikan_ditolak')->value('isi'));
    }

    public function test_verification_and_rejection_notify(): void
    {
        $this->nasabah->forceFill(['status_verifikasi' => 'pending'])->save();
        app(NasabahService::class)->verifikasi($this->petugas, $this->nasabah->fresh());
        $this->assertContains('akun_terverifikasi', $this->tipe($this->nasabah->user));

        $this->lain->forceFill(['status_verifikasi' => 'pending'])->save();
        app(NasabahService::class)->tolak($this->petugas, $this->lain->fresh(), 'Data tidak sesuai');
        $this->assertContains('akun_ditolak', $this->tipe($this->lain->user));
    }

    public function test_biopori_activity_and_titik_decisions_notify(): void
    {
        $titik = TitikBiopori::factory()->forBank($this->bank)->create();
        $res = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [
            'titik_biopori_id' => $titik->id, 'tanggal_pemasukan' => now()->subMinute()->toDateTimeString(), 'jenis_sampah' => 'Sisa sayur', 'berat_kg' => 2,
            'foto_bukti' => UploadedFile::fake()->image('b.jpg', 300, 300),
        ])->assertCreated();
        $this->actingAsApi($this->petugas)->postJson('/api/organik/biopori/'.$res->json('data.id').'/approve')->assertOk();

        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', ['nama_lokasi' => 'Pojok Taman', 'latitude' => -7.25, 'longitude' => 112.75])->assertCreated()->json('data.id');
        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/lokasi/{$id}/reject", ['catatan' => 'Terlalu dekat jalan'])->assertOk();

        $tipe = $this->tipe($this->nasabah->user);
        $this->assertContains('biopori_disetujui', $tipe);
        $this->assertContains('titik_ditolak', $tipe);
        $this->assertStringContainsString('Terlalu dekat jalan', Notifikasi::query()->where('tipe', 'titik_ditolak')->value('isi'));
    }

    public function test_api_lists_only_own_and_marks_read(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 1);
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->lain, $this->jenis, 1);
        $milikLain = Notifikasi::query()->where('user_id', $this->lain->user->id)->first();

        $this->actingAsApi($this->nasabah->user)->getJson('/api/notifikasi')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('belum_dibaca', 1)
            ->assertJsonStructure(['data' => ['*' => ['id', 'tipe', 'judul', 'isi', 'aksi', 'dibaca', 'created_at']], 'belum_dibaca']);
        $this->getJson('/api/notifikasi/jumlah')->assertOk()->assertJsonPath('belum_dibaca', 1);

        // Tidak bisa menandai notifikasi orang lain.
        $this->postJson("/api/notifikasi/{$milikLain->id}/baca")->assertNotFound();
        $this->assertNull($milikLain->fresh()->dibaca_at);

        $punya = Notifikasi::query()->where('user_id', $this->nasabah->user->id)->first();
        $this->postJson("/api/notifikasi/{$punya->id}/baca")->assertOk()->assertJsonPath('belum_dibaca', 0);
        $this->getJson('/api/notifikasi')->assertJsonPath('data.0.dibaca', true);
    }

    public function test_mark_all_read_does_not_touch_others(): void
    {
        $svc = app(TransaksiService::class);
        $svc->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 1);
        $svc->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 2);
        $svc->catatAnorganik($this->petugas, $this->lain, $this->jenis, 1);

        $this->actingAsApi($this->nasabah->user)->postJson('/api/notifikasi/baca-semua')->assertOk()->assertJsonPath('belum_dibaca', 0);

        $this->assertSame(1, Notifikasi::query()->whereNull('dibaca_at')->count());
        $this->assertSame($this->lain->user->id, Notifikasi::query()->whereNull('dibaca_at')->value('user_id'));
    }

    public function test_staff_and_guests_cannot_use_the_endpoints(): void
    {
        $this->getJson('/api/notifikasi')->assertUnauthorized();
        $this->actingAsApi($this->petugas)->getJson('/api/notifikasi')->assertForbidden();
    }

    public function test_cleanup_command_removes_old_notifications(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 1);
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 2);
        Notifikasi::query()->orderBy('id')->first()->forceFill(['created_at' => now()->subDays(120)])->save();

        $this->artisan('ecowin:bersihkan-notifikasi')->assertSuccessful();

        $this->assertSame(1, Notifikasi::query()->count());
    }

    public function test_notification_failure_never_breaks_the_main_action(): void
    {
        \Illuminate\Support\Facades\Schema::drop('notifikasi'); // simulasi tabel bermasalah

        $trx = app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 2);

        $this->assertSame(4000, (int) $trx->nilai_rupiah);
        $this->assertEquals(4000, $this->nasabah->fresh()->saldo);
    }
}
