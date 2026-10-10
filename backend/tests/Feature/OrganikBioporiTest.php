<?php

namespace Tests\Feature;

use App\Models\AktivitasBiopori;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganikBioporiTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private User $petugas;

    private Nasabah $nasabah;

    private TitikBiopori $titik;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create();
        $this->titik = TitikBiopori::factory()->forBank($this->bank)->create();
    }

    public function test_organik_input_does_not_change_balance_and_estimates_compost(): void
    {
        config(['ecowin.kompos.rasio_per_metode.komposter' => 0.6]);

        $this->actingAsApi($this->petugas)->postJson('/api/organik', [
            'nasabah_id' => $this->nasabah->id, 'jenis_organik' => 'Sisa sayur', 'berat_kg' => 10,
            'metode_pengolahan' => 'komposter', 'lokasi' => 'Komposter RT', 'checklist_bebas_plastik' => true, 'checklist_bebas_logam' => true,
        ])->assertCreated()->assertJsonPath('data.estimasi_kompos_kg', 6)->assertJsonPath('data.status_pengolahan', 'diproses');

        $this->assertEquals(0, $this->nasabah->fresh()->saldo);
        $this->assertDatabaseCount('mutasi_saldo', 0);
    }

    public function test_organik_requires_plastic_and_metal_free_checklist(): void
    {
        $this->actingAsApi($this->petugas)->postJson('/api/organik', [
            'nasabah_id' => $this->nasabah->id, 'jenis_organik' => 'Campur', 'berat_kg' => 2, 'checklist_bebas_plastik' => false, 'checklist_bebas_logam' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('checklist_bebas_plastik');
    }

    public function test_nasabah_reports_biopori_activity_with_photo_status_pending(): void
    {
        $response = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.jenis_sampah', 'Sisa sayur');

        $aktivitas = AktivitasBiopori::findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($aktivitas->foto_bukti_path);
        Storage::disk('public')->assertMissing($aktivitas->foto_bukti_path);
        $this->assertSame($this->titik->id, $aktivitas->data_awal['titik_biopori_id']);
        $this->assertStringEndsWith('.jpg', $aktivitas->foto_bukti_path);
    }

    public function test_photo_is_required(): void
    {
        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [...$this->laporan(), 'foto_bukti' => null])
            ->assertStatus(422)->assertJsonValidationErrors('foto_bukti');
    }

    public function test_non_image_and_disguised_php_uploads_are_rejected(): void
    {
        $this->actingAsApi($this->nasabah->user);

        $php = UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>');
        $this->postJson('/api/organik/biopori', [...$this->laporan(), 'foto_bukti' => $php])->assertStatus(422)->assertJsonValidationErrors('foto_bukti');

        $pdf = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');
        $this->postJson('/api/organik/biopori', [...$this->laporan(), 'foto_bukti' => $pdf])->assertStatus(422);

        $besar = UploadedFile::fake()->image('besar.jpg')->size(config('ecowin.upload.foto_max_kb') + 100);
        $this->postJson('/api/organik/biopori', [...$this->laporan(), 'foto_bukti' => $besar])->assertStatus(422);
    }

    public function test_cannot_report_to_biopori_location_of_other_bank(): void
    {
        $other = TitikBiopori::factory()->forBank(BankSampah::factory()->create())->create();

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [...$this->laporan(), 'titik_biopori_id' => $other->id])
            ->assertStatus(422)->assertJsonValidationErrors('titik_biopori_id');
    }

    public function test_staff_cannot_submit_biopori_report_as_nasabah(): void
    {
        $this->actingAsApi($this->petugas)->postJson('/api/organik/biopori', $this->laporan())->assertForbidden();
    }

    public function test_method_is_stored_and_defaults_from_location(): void
    {
        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())
            ->assertCreated()->assertJsonPath('data.metode_pengolahan', 'bioporiprint')->assertJsonPath('data.metode', 'BioporiPrint');

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [...$this->laporan(), 'metode_pengolahan' => 'lainnya'])
            ->assertCreated()->assertJsonPath('data.metode_pengolahan', 'lainnya');
    }

    public function test_bioporiprint_method_requires_bioporiprint_location(): void
    {
        $this->titik->forceFill(['bioporiprint' => false])->save();

        $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', [...$this->laporan(), 'metode_pengolahan' => 'bioporiprint'])
            ->assertStatus(422)->assertJsonValidationErrors('metode_pengolahan');
    }

    public function test_approved_activity_becomes_completed_when_location_is_harvested(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())->json('data.id');
        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/{$id}/approve")->assertOk();

        app(\App\Services\BioporiService::class)->tandaiPanen($this->petugas, $this->titik->fresh(), 5);

        $this->assertSame('completed', AktivitasBiopori::findOrFail($id)->status);
    }

    public function test_petugas_approves_activity_and_harvest_estimate_is_updated(): void
    {
        config(['ecowin.biopori.hari_panen' => 60]);
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())->json('data.id');

        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/{$id}/approve", ['catatan' => 'Sesuai foto'])
            ->assertOk()->assertJsonPath('data.status', 'approved');

        $aktivitas = AktivitasBiopori::findOrFail($id);
        $this->assertSame($this->petugas->id, $aktivitas->diperiksa_oleh);
        $this->assertNotNull($aktivitas->waktu_diperiksa);

        $titik = $this->titik->fresh();
        $this->assertTrue($titik->terakhir_diisi_at->equalTo($aktivitas->tanggal_pemasukan));
        $this->assertTrue($titik->estimasi_panen_at->equalTo($aktivitas->tanggal_pemasukan->copy()->addDays(60)));
        $this->assertSame(TitikBiopori::PANEN_BELUM, $titik->statusPanenEfektif());

        $this->travel(61)->days();
        $this->assertSame(TitikBiopori::PANEN_SIAP, $titik->fresh()->statusPanenEfektif());

        $this->postJson("/api/organik/biopori/{$id}/reject", ['catatan' => 'x'])->assertStatus(422);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'approve_biopori', 'model_id' => $id]);
    }

    public function test_petugas_rejects_activity_with_note(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())->json('data.id');

        $this->actingAsApi($this->petugas)->postJson("/api/organik/biopori/{$id}/reject")->assertStatus(422);
        $this->postJson("/api/organik/biopori/{$id}/reject", ['catatan' => 'Foto buram'])->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->assertNull($this->titik->fresh()->terakhir_diisi_at);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'reject_biopori']);
    }

    public function test_photo_is_served_only_to_authorized_users(): void
    {
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/organik/biopori', $this->laporan())->json('data.id');

        $this->getJson("/api/organik/biopori/{$id}/foto")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAsApi($this->petugas)->getJson("/api/organik/biopori/{$id}/foto")->assertOk();

        $this->actingAsApi(Nasabah::factory()->forBank($this->bank)->create()->user)->getJson("/api/organik/biopori/{$id}/foto")->assertForbidden();
        $this->actingAsApi(User::factory()->petugas()->create())->getJson("/api/organik/biopori/{$id}/foto")->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function laporan(): array
    {
        return [
            'titik_biopori_id' => $this->titik->id,
            'tanggal_pemasukan' => now()->subHour()->toDateTimeString(),
            'jenis_sampah' => 'Sisa sayur',
            'berat_kg' => 1.5,
            'catatan' => 'Dimasukkan pagi hari',
            'foto_bukti' => UploadedFile::fake()->image('bukti.jpg', 800, 600),
        ];
    }
}
