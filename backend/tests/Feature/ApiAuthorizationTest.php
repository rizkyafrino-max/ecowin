<?php

namespace Tests\Feature;

use App\Models\AktivitasBiopori;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TitikBiopori;
use App\Models\TransaksiAnorganik;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin melihat semua; Petugas hanya Bank Sampah sendiri; Nasabah hanya data sendiri.
 */
class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bankA;

    private BankSampah $bankB;

    private User $admin;

    private User $petugasA;

    private Nasabah $nasabahA;

    private Nasabah $nasabahB;

    private TransaksiAnorganik $trxA;

    private TransaksiAnorganik $trxB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create();
        $this->bankB = BankSampah::factory()->create();
        $this->petugasA = User::factory()->petugas($this->bankA)->create();
        $petugasB = User::factory()->petugas($this->bankB)->create();
        $this->nasabahA = Nasabah::factory()->forBank($this->bankA)->create();
        $this->nasabahB = Nasabah::factory()->forBank($this->bankB)->create();

        $jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 2000]);

        $service = app(TransaksiService::class);
        $this->trxA = $service->catatAnorganik($this->petugasA, $this->nasabahA, $jenis, 5);
        $this->trxB = $service->catatAnorganik($petugasB, $this->nasabahB, $jenis, 5);
    }

    public function test_admin_sees_all_banks(): void
    {
        $this->actingAsApi($this->admin)->getJson('/api/nasabah')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/transaksi/anorganik')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/nasabah/{$this->nasabahB->id}")->assertOk();
    }

    public function test_petugas_only_sees_own_bank(): void
    {
        $this->actingAsApi($this->petugasA)->getJson('/api/nasabah')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->nasabahA->id);
        $this->getJson('/api/transaksi/anorganik')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->trxA->id);
    }

    public function test_petugas_cannot_access_other_bank_by_id(): void
    {
        $this->actingAsApi($this->petugasA);

        $this->getJson("/api/nasabah/{$this->nasabahB->id}")->assertForbidden();
        $this->getJson("/api/transaksi/anorganik/{$this->trxB->id}")->assertForbidden();
        $this->getJson('/api/nasabah/scan/'.$this->nasabahB->kartu_qr_token)->assertNotFound();
        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabahB->id, 'jenis_sampah_id' => $this->trxB->hargaSampah->jenis_sampah_id, 'berat_kg' => 1])->assertForbidden();
        $this->postJson('/api/penarikan', ['nasabah_id' => $this->nasabahB->id, 'jumlah' => 1000])->assertForbidden();
        $this->postJson('/api/koreksi', ['tipe_transaksi' => 'anorganik', 'transaksi_id' => $this->trxB->id, 'berat_kg' => 1, 'alasan' => 'percobaan manipulasi id'])->assertStatus(422);
    }

    public function test_petugas_cannot_process_other_bank_withdrawal_or_biopori(): void
    {
        $penarikan = $this->penarikanFor($this->nasabahB);
        $aktivitas = $this->aktivitasFor($this->nasabahB);

        $this->actingAsApi($this->petugasA);
        $this->postJson("/api/penarikan/{$penarikan->id}/approve")->assertForbidden();
        $this->postJson("/api/organik/biopori/{$aktivitas->id}/approve")->assertForbidden();
        $this->getJson("/api/organik/biopori/{$aktivitas->id}/foto")->assertForbidden();

        $this->assertSame(PenarikanSaldo::STATUS_PENDING, $penarikan->fresh()->status);
        $this->assertSame(AktivitasBiopori::STATUS_PENDING, $aktivitas->fresh()->status);
    }

    public function test_nasabah_only_sees_own_data(): void
    {
        $this->actingAsApi($this->nasabahA->user);

        $this->getJson('/api/transaksi/anorganik')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nasabah_id', $this->nasabahA->id);
        $this->getJson("/api/nasabah/{$this->nasabahA->id}")->assertOk();
        $this->getJson('/api/me/saldo')->assertOk()->assertJsonPath('saldo', 10000);
        $this->getJson('/api/dashboard/summary')->assertOk()->assertJsonPath('umum.total_nasabah', 1)->assertJsonPath('anorganik.jumlah_transaksi', 1);
    }

    public function test_nasabah_cannot_access_other_nasabah(): void
    {
        $penarikan = $this->penarikanFor($this->nasabahB);
        $aktivitas = $this->aktivitasFor($this->nasabahB);

        $this->actingAsApi($this->nasabahA->user);
        $this->getJson("/api/nasabah/{$this->nasabahB->id}")->assertForbidden();
        $this->getJson("/api/transaksi/anorganik/{$this->trxB->id}")->assertForbidden();
        $this->getJson("/api/penarikan/{$penarikan->id}")->assertForbidden();
        $this->getJson("/api/organik/biopori/{$aktivitas->id}")->assertForbidden();
        $this->getJson("/api/organik/biopori/{$aktivitas->id}/foto")->assertForbidden();
        $this->getJson('/api/nasabah')->assertForbidden();
    }

    public function test_nasabah_cannot_use_staff_endpoints(): void
    {
        $this->actingAsApi($this->nasabahA->user);

        $this->postJson('/api/harga', ['jenis_sampah_id' => 1, 'harga_per_kg' => 999999])->assertForbidden();
        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabahA->id, 'jenis_sampah_id' => 1, 'berat_kg' => 100])->assertForbidden();
        $this->postJson('/api/koreksi', [])->assertForbidden();
        $this->postJson('/api/koreksi/1/approve')->assertForbidden();
        $this->getJson('/api/audit-log')->assertForbidden();
        $this->patchJson('/api/laporan-kendala/1', ['status' => 'disetujui'])->assertForbidden();
        $this->postJson('/api/penarikan/1/approve')->assertForbidden();
    }

    public function test_petugas_cannot_use_admin_only_endpoints(): void
    {
        $this->actingAsApi($this->petugasA);

        $this->postJson('/api/harga', ['jenis_sampah_id' => 1, 'harga_per_kg' => 999999])->assertForbidden();
        $this->getJson('/api/audit-log')->assertForbidden();
    }

    public function test_admin_endpoints_work_for_admin(): void
    {
        $this->actingAsApi($this->admin)->getJson('/api/audit-log')->assertOk();
    }

    private function penarikanFor(Nasabah $nasabah): PenarikanSaldo
    {
        $p = new PenarikanSaldo;
        $p->forceFill(['nasabah_id' => $nasabah->id, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'jumlah' => 1000, 'status' => 'pending'])->save();

        return $p;
    }

    private function aktivitasFor(Nasabah $nasabah): AktivitasBiopori
    {
        $titik = TitikBiopori::factory()->forBank($nasabah->bankSampah)->create();
        $a = new AktivitasBiopori;
        $a->forceFill(['nasabah_id' => $nasabah->id, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'titik_biopori_id' => $titik->id, 'tanggal_pemasukan' => now(), 'berat_kg' => 1, 'foto_bukti_path' => 'x.jpg', 'status' => 'pending'])->save();

        return $a;
    }
}
