<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cetak laporan: Admin boleh semua Bank Sampah atau satu pilihan; Petugas hanya Bank Sampah yang dikelolanya.
 */
class LaporanCetakTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugasA;

    private User $petugasB;

    private BankSampah $bankA;

    private BankSampah $bankB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create(['nama_bank_sampah' => 'Bank Alfa Sejahtera']);
        $this->bankB = BankSampah::factory()->create(['nama_bank_sampah' => 'Bank Beta Makmur']);
        $this->petugasA = User::factory()->petugas($this->bankA)->create();
        $this->petugasB = User::factory()->petugas($this->bankB)->create();

        $jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 2000]);

        $nasabahA = Nasabah::factory()->forBank($this->bankA)->create(['nama' => 'Nasabah Di Alfa']);
        $nasabahB = Nasabah::factory()->forBank($this->bankB)->create(['nama' => 'Nasabah Di Beta']);
        app(TransaksiService::class)->catatAnorganik($this->petugasA, $nasabahA, $jenis, 3);
        app(TransaksiService::class)->catatAnorganik($this->petugasB, $nasabahB, $jenis, 5);
    }

    public function test_admin_prints_all_banks_with_per_bank_recap(): void
    {
        $this->actingAs($this->admin)->get(route('admin.laporan.cetak'))
            ->assertOk()
            ->assertSee('Semua Bank Sampah')
            ->assertSee('Rekap per Bank Sampah')
            ->assertSee('Bank Alfa Sejahtera')->assertSee('Bank Beta Makmur')
            ->assertSee('Nasabah Di Alfa')->assertSee('Nasabah Di Beta')
            ->assertHeader('X-Robots-Tag', 'noindex');
    }

    public function test_admin_can_choose_one_bank(): void
    {
        $this->actingAs($this->admin)->get(route('admin.laporan.cetak', ['bank' => $this->bankA->id]))
            ->assertOk()
            ->assertSee('Bank Alfa Sejahtera')
            ->assertSee('Nasabah Di Alfa')
            ->assertDontSee('Nasabah Di Beta')
            ->assertDontSee('Rekap per Bank Sampah');
    }

    public function test_petugas_is_locked_to_own_bank_even_if_other_bank_requested(): void
    {
        $this->actingAs($this->petugasA)->get(route('admin.laporan.cetak', ['bank' => $this->bankB->id]))
            ->assertOk()
            ->assertSee('Bank Alfa Sejahtera')
            ->assertSee('Nasabah Di Alfa')
            ->assertDontSee('Nasabah Di Beta')
            ->assertDontSee('Bank Beta Makmur');

        $this->actingAs($this->petugasB)->get(route('admin.laporan.cetak', ['bank' => 'semua']))
            ->assertOk()
            ->assertSee('Nasabah Di Beta')
            ->assertDontSee('Nasabah Di Alfa')
            ->assertDontSee('Rekap per Bank Sampah');
    }

    public function test_nasabah_and_guests_are_blocked(): void
    {
        $nasabah = Nasabah::factory()->forBank($this->bankA)->create();

        $this->actingAs($nasabah->user)->get(route('admin.laporan.cetak'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.laporan.cetak'))->assertRedirect();
    }

    public function test_inactive_staff_is_blocked(): void
    {
        $this->petugasA->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        $this->actingAs($this->petugasA)->get(route('admin.laporan.cetak'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_invalid_parameters_are_rejected(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.laporan.cetak', ['bank' => 'abc']))->assertSessionHasErrors('bank');
        $this->get(route('admin.laporan.cetak', ['bank' => 999999]))->assertStatus(422);
        $this->get(route('admin.laporan.cetak', ['dari' => '2026-10-10', 'sampai' => '2026-10-01']))->assertSessionHasErrors('sampai');
        $this->get(route('admin.laporan.cetak', ['bank' => '1; DROP TABLE users']))->assertSessionHasErrors('bank');
    }

    public function test_print_is_audited_and_not_cacheable(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('admin.laporan.cetak', ['bank' => $this->bankA->id]));
        $resp->assertOk();
        $this->assertStringContainsString('no-store', (string) $resp->headers->get('Cache-Control'));

        $this->assertDatabaseHas('audit_log', ['aksi' => 'cetak_laporan', 'user_id' => $this->admin->id]);
    }

    public function test_period_filter_limits_the_rows(): void
    {
        $this->actingAs($this->admin)->get(route('admin.laporan.cetak', ['dari' => now()->addDays(5)->toDateString()]))
            ->assertOk()
            ->assertSee('Tidak ada transaksi pada periode ini.')
            ->assertDontSee('Nasabah Di Alfa');
    }

    public function test_service_bank_filter_is_ignored_for_petugas(): void
    {
        $service = app(LaporanService::class);

        $admin = $service->ringkasan($this->admin, null, null, $this->bankA->id);
        $petugasMencoba = $service->ringkasan($this->petugasB, null, null, $this->bankA->id);
        $semua = $service->ringkasan($this->admin);

        $this->assertEquals(3.0, $admin['anorganik']['total_berat_kg']);
        $this->assertEquals(5.0, $petugasMencoba['anorganik']['total_berat_kg']); // tetap bank miliknya (B)
        $this->assertEquals(8.0, $semua['anorganik']['total_berat_kg']);
    }

    public function test_laporan_page_has_bank_selector_for_admin_only(): void
    {
        $this->actingAs($this->admin)->get('/admin/laporan')->assertOk()->assertSee('Cetak laporan')->assertSee('Semua Bank Sampah');
        $this->actingAs($this->petugasA)->get('/admin/laporan')->assertOk()->assertSee('Cetak laporan')->assertDontSee('Semua Bank Sampah');
    }
}
