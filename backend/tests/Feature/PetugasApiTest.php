<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API untuk aplikasi petugas di Android: daftar nasabah menunggu, verifikasi/tolak, dan ringkasan beranda. */
class PetugasApiTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private User $petugas;

    private Nasabah $pending;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->pending = Nasabah::factory()->forBank($this->bank)->create();
        $this->pending->forceFill(['status_verifikasi' => 'pending'])->save();
    }

    public function test_petugas_can_list_only_pending_nasabah_of_own_bank(): void
    {
        $verified = Nasabah::factory()->forBank($this->bank)->create();
        $verified->forceFill(['status_verifikasi' => 'verified'])->save();
        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $lain->forceFill(['status_verifikasi' => 'pending'])->save();

        $ids = collect($this->actingAsApi($this->petugas)->getJson('/api/nasabah?status_verifikasi=pending')->assertOk()->json('data'))->pluck('id');

        $this->assertEquals([$this->pending->id], $ids->all());
    }

    public function test_petugas_can_verify_nasabah(): void
    {
        $this->actingAsApi($this->petugas)->postJson("/api/nasabah/{$this->pending->id}/verifikasi")->assertOk();

        $this->assertSame('verified', $this->pending->fresh()->status_verifikasi);
    }

    public function test_petugas_can_reject_nasabah_with_reason(): void
    {
        $this->actingAsApi($this->petugas)->postJson("/api/nasabah/{$this->pending->id}/tolak", ['alasan' => 'x'])->assertUnprocessable();
        $this->actingAsApi($this->petugas)->postJson("/api/nasabah/{$this->pending->id}/tolak", ['alasan' => 'Data tidak sesuai'])->assertOk();

        $this->assertSame('nonaktif', $this->pending->fresh()->status);
    }

    public function test_petugas_cannot_verify_nasabah_of_other_bank(): void
    {
        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $lain->forceFill(['status_verifikasi' => 'pending'])->save();

        $this->actingAsApi($this->petugas)->postJson("/api/nasabah/{$lain->id}/verifikasi")->assertForbidden();
        $this->assertSame('pending', $lain->fresh()->status_verifikasi);
    }

    public function test_nasabah_cannot_use_staff_endpoints(): void
    {
        $this->actingAsApi($this->pending->user)->postJson("/api/nasabah/{$this->pending->id}/verifikasi")->assertForbidden();
        $this->actingAsApi($this->pending->user)->getJson('/api/petugas/ringkasan')->assertForbidden();
    }

    public function test_ringkasan_counts_work_for_own_bank(): void
    {
        $this->actingAsApi($this->petugas)->getJson('/api/petugas/ringkasan')->assertOk()
            ->assertJsonPath('menunggu_verifikasi', 1)
            ->assertJsonPath('penarikan_menunggu', 0)
            ->assertJsonPath('setoran_hari_ini.jumlah', 0)
            ->assertJsonPath('bank_sampah.id', $this->bank->id);
    }
}
