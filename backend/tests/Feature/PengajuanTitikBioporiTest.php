<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nasabah mengajukan titik Biopori baru; titik baru menunggu verifikasi petugas Bank Sampah yang sama.
 */
class PengajuanTitikBioporiTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private User $petugas;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create();
    }

    private function payload(array $override = []): array
    {
        return array_merge(['nama_lokasi' => 'Pojok Taman RT 03', 'latitude' => -7.2575, 'longitude' => 112.7521, 'deskripsi_lokasi' => 'Di samping pos ronda'], $override);
    }

    public function test_nasabah_submits_titik_pending_and_it_is_not_official_yet(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.verifikasi', 'pending')
            ->assertJsonPath('data.diajukan_saya', true)
            ->json('data.id');

        $titik = TitikBiopori::findOrFail($id);
        $this->assertSame($this->bank->id, (int) $titik->bank_sampah_id);
        $this->assertSame($this->nasabah->user->id, (int) $titik->diajukan_oleh);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'ajukan_titik_biopori', 'model_id' => $id]);
        $this->assertCount(0, TitikBiopori::query()->disetujui()->where('id', $id)->get());
    }

    public function test_bank_sampah_is_taken_from_account_not_from_input(): void
    {
        $lain = BankSampah::factory()->create();

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload(['bank_sampah_id' => $lain->id, 'verifikasi' => 'approved']))
            ->assertCreated()->assertJsonPath('data.verifikasi', 'pending');

        $titik = TitikBiopori::query()->latest('id')->first();
        $this->assertSame($this->bank->id, (int) $titik->bank_sampah_id);
    }

    public function test_pending_titik_cannot_be_used_to_report_activity(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())->json('data.id');

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [
            'titik_biopori_id' => $id, 'tanggal_pemasukan' => now()->toDateTimeString(), 'jenis_sampah' => 'Sisa sayur', 'berat_kg' => 2,
            'foto_bukti' => \Illuminate\Http\UploadedFile::fake()->image('b.jpg', 300, 300),
        ])->assertStatus(422)->assertJsonValidationErrors('titik_biopori_id');
    }

    public function test_other_nasabah_does_not_see_the_pending_submission_but_petugas_does(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())->json('data.id');
        $lain = Nasabah::factory()->forBank($this->bank)->create();

        $this->actingAsApi($lain->user)->getJson('/api/organik/biopori/lokasi')->assertOk()
            ->assertJsonMissing(['id' => $id]);
        $this->actingAsApi($this->nasabah->user)->getJson('/api/organik/biopori/lokasi')->assertOk()
            ->assertJsonFragment(['id' => $id]);
        $this->actingAsApi($this->petugas)->getJson('/api/organik/biopori/lokasi')->assertOk()
            ->assertJsonFragment(['id' => $id]);
    }

    public function test_petugas_approves_and_then_titik_is_official(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())->json('data.id');

        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/lokasi/{$id}/approve")
            ->assertOk()->assertJsonPath('data.verifikasi', 'approved');

        $this->assertDatabaseHas('titik_biopori', ['id' => $id, 'verifikasi' => 'approved', 'diverifikasi_oleh' => $this->petugas->id]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'setujui_titik_biopori', 'model_id' => $id]);

        $lain = Nasabah::factory()->forBank($this->bank)->create();
        $this->actingAsApi($lain->user)->getJson('/api/organik/biopori/lokasi')->assertJsonFragment(['id' => $id]);

        // Sudah diputuskan: tidak bisa diputuskan lagi.
        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/lokasi/{$id}/reject", ['catatan' => 'salah'])->assertStatus(422);
    }

    public function test_reject_requires_reason_and_nasabah_sees_it(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())->json('data.id');

        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/lokasi/{$id}/reject")->assertStatus(422)->assertJsonValidationErrors('catatan');
        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/lokasi/{$id}/reject", ['catatan' => 'Titik terlalu dekat jalan'])
            ->assertOk()->assertJsonPath('data.verifikasi', 'rejected');

        $this->actingAsApi($this->nasabah->user)->getJson('/api/organik/biopori/lokasi')
            ->assertJsonFragment(['id' => $id, 'verifikasi' => 'rejected', 'catatan_verifikasi' => 'Titik terlalu dekat jalan']);
    }

    public function test_petugas_of_another_bank_and_nasabah_cannot_decide(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())->json('data.id');
        $petugasLain = User::factory()->petugas(BankSampah::factory()->create())->create();

        $this->actingAsApi($petugasLain)->postJson("/api/organik/biopori/lokasi/{$id}/approve")->assertForbidden();
        $this->actingAsApi($this->nasabah->user)->postJson("/api/organik/biopori/lokasi/{$id}/approve")->assertForbidden();
        $this->assertDatabaseHas('titik_biopori', ['id' => $id, 'verifikasi' => 'pending']);
    }

    public function test_staff_cannot_use_the_nasabah_submission_endpoint(): void
    {
        $this->actingAsApi($this->petugas)->postJson('/api/organik/biopori/lokasi', $this->payload())->assertForbidden();
    }

    public function test_validation_rejects_bad_coordinates_and_names(): void
    {
        $this->actingAsApi($this->nasabah->user);

        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['latitude' => 48.85, 'longitude' => 2.35]))
            ->assertStatus(422)->assertJsonValidationErrors(['latitude', 'longitude']);
        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['nama_lokasi' => 'ab']))->assertStatus(422)->assertJsonValidationErrors('nama_lokasi');
        $this->postJson('/api/organik/biopori/lokasi', ['nama_lokasi' => 'Taman'])->assertStatus(422)->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_limits_pending_submissions_and_blocks_duplicates(): void
    {
        $this->actingAsApi($this->nasabah->user);

        $this->postJson('/api/organik/biopori/lokasi', $this->payload())->assertCreated();
        // Titik yang sama (jarak < 15 m) ditolak.
        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['nama_lokasi' => 'Duplikat', 'latitude' => -7.25751]))
            ->assertStatus(422)->assertJsonValidationErrors('latitude');

        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['nama_lokasi' => 'Titik dua', 'latitude' => -7.2600]))->assertCreated();
        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['nama_lokasi' => 'Titik tiga', 'latitude' => -7.2650]))->assertCreated();
        $this->postJson('/api/organik/biopori/lokasi', $this->payload(['nama_lokasi' => 'Titik empat', 'latitude' => -7.2700]))
            ->assertStatus(422)->assertJsonValidationErrors('nama_lokasi');
    }

    public function test_unverified_nasabah_cannot_submit(): void
    {
        $this->nasabah->forceFill(['status_verifikasi' => 'pending'])->save();

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori/lokasi', $this->payload())
            ->assertStatus(422)->assertJsonValidationErrors('nama_lokasi');
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/organik/biopori/lokasi', $this->payload())->assertUnauthorized();
    }
}
