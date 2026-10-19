<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ekspor CSV laporan: hak akses sama dengan cetak; Petugas hanya Bank Sampah miliknya; sel dinetralkan dari injeksi rumus. */
class LaporanEksporTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugasA;

    private BankSampah $bankA;

    private BankSampah $bankB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create(['nama_bank_sampah' => 'Bank Alfa']);
        $this->bankB = BankSampah::factory()->create(['nama_bank_sampah' => 'Bank Beta']);
        $this->petugasA = User::factory()->petugas($this->bankA)->create();
        $petugasB = User::factory()->petugas($this->bankB)->create();

        $jenis = JenisSampah::factory()->create(['nama_jenis' => 'Kardus']);
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 2000]);
        $a = Nasabah::factory()->forBank($this->bankA)->create(['nama' => '=HYPERLINK("http://jahat")']);
        $b = Nasabah::factory()->forBank($this->bankB)->create(['nama' => 'Nasabah Beta']);
        app(TransaksiService::class)->catatAnorganik($this->petugasA, $a, $jenis, 2.5);
        app(TransaksiService::class)->catatAnorganik($petugasB, $b, $jenis, 4);
    }

    private function csv(User $user, string $jenis, array $query = []): string
    {
        return $this->actingAs($user)->get(route('admin.laporan.ekspor', ['jenis' => $jenis] + $query))->assertOk()->streamedContent();
    }

    public function test_admin_exports_all_banks_with_excel_friendly_format(): void
    {
        $r = $this->actingAs($this->admin)->get(route('admin.laporan.ekspor', ['jenis' => 'anorganik']))->assertOk();
        $r->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', (string) $r->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));

        $isi = $r->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $isi);
        $this->assertStringContainsString('Tanggal;"Nomor nasabah";Nasabah', $isi);
        $this->assertStringContainsString('Bank Alfa', $isi);
        $this->assertStringContainsString('Bank Beta', $isi);
        $this->assertStringContainsString('2,50', $isi); // koma desimal
    }

    public function test_formula_injection_is_neutralised(): void
    {
        $isi = $this->csv($this->admin, 'anorganik');

        $this->assertStringContainsString("'=HYPERLINK", $isi);
        $this->assertStringNotContainsString(';=HYPERLINK', $isi);
    }

    public function test_petugas_only_gets_own_bank_even_if_other_bank_requested(): void
    {
        $isi = $this->csv($this->petugasA, 'anorganik', ['bank' => $this->bankB->id]);

        $this->assertStringContainsString('Bank Alfa', $isi);
        $this->assertStringNotContainsString('Bank Beta', $isi);
        $this->assertStringNotContainsString('Nasabah Beta', $isi);
    }

    public function test_admin_can_filter_one_bank_and_rekap_works(): void
    {
        $isi = $this->csv($this->admin, 'anorganik', ['bank' => $this->bankB->id]);
        $this->assertStringContainsString('Nasabah Beta', $isi);
        $this->assertStringNotContainsString('Bank Alfa', $isi);

        $rekap = $this->csv($this->admin, 'rekap');
        $this->assertStringContainsString('Bank Alfa', $rekap);
        $this->assertStringContainsString('Bank Beta', $rekap);

        $rekapPetugas = $this->csv($this->petugasA, 'rekap');
        $this->assertStringContainsString('Bank Alfa', $rekapPetugas);
        $this->assertStringNotContainsString('Bank Beta', $rekapPetugas);
    }

    public function test_all_types_work_and_unknown_type_is_404(): void
    {
        foreach (['anorganik', 'organik', 'penarikan', 'rekap'] as $j) {
            $this->assertStringStartsWith("\xEF\xBB\xBF", $this->csv($this->admin, $j));
        }
        $this->actingAs($this->admin)->get('/admin/laporan/ekspor/pengguna')->assertNotFound();
    }

    public function test_nasabah_guest_and_bad_params_are_blocked_and_export_is_audited(): void
    {
        $nasabah = Nasabah::factory()->forBank($this->bankA)->create();
        $this->actingAs($nasabah->user)->get(route('admin.laporan.ekspor', ['jenis' => 'anorganik']))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.laporan.ekspor', ['jenis' => 'anorganik', 'bank' => 'abc']))->assertSessionHasErrors('bank');
        $this->actingAs($this->admin)->get(route('admin.laporan.ekspor', ['jenis' => 'anorganik', 'bank' => 99999]))->assertStatus(422);

        $this->csv($this->admin, 'penarikan');
        $this->assertDatabaseHas('audit_log', ['aksi' => 'ekspor_laporan', 'user_id' => $this->admin->id]);
    }

    public function test_laporan_page_shows_export_menu(): void
    {
        $this->actingAs($this->admin)->get('/admin/laporan')->assertOk()->assertSee('Ekspor CSV')->assertSee('Setoran anorganik (CSV)');
    }
}
