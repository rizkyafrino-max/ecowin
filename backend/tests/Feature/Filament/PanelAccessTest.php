<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\BankSampahs\BankSampahResource;
use App\Filament\Resources\HargaSampahs\HargaSampahResource;
use App\Filament\Resources\JenisSampahs\JenisSampahResource;
use App\Filament\Resources\KategoriSampahs\KategoriSampahResource;
use App\Filament\Resources\KoreksiTransaksis\KoreksiTransaksiResource;
use App\Filament\Resources\Nasabahs\NasabahResource;
use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use App\Filament\Resources\TitikBioporis\TitikBioporiResource;
use App\Filament\Resources\TransaksiAnorganiks\TransaksiAnorganikResource;
use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private BankSampah $bankA;

    private BankSampah $bankB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create();
        $this->bankB = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bankA)->create();
    }

    public function test_admin_can_open_every_page_with_indigo_identity(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin')->assertOk()->assertSee('Control Center EcoWin')->assertSee('ADMIN')->assertSee('#4F46E5')
            ->assertSee('Bank Sampah teratas')->assertSee('Jenis sampah teratas')->assertSee('eco-search', false);

        foreach ([UserResource::class, BankSampahResource::class, KategoriSampahResource::class, JenisSampahResource::class, HargaSampahResource::class,
            NasabahResource::class, TransaksiAnorganikResource::class, TransaksiOrganikResource::class, AktivitasBioporiResource::class,
            TitikBioporiResource::class, PenarikanSaldoResource::class, KoreksiTransaksiResource::class, AuditLogResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        $this->get('/admin/laporan')->assertOk()->assertSee('Anorganik')->assertSee('Organik')->assertSee('Keuangan');
        $this->get('/admin/organik/peta-biopori')->assertOk();
        $this->get('/admin/pengaturan')->assertOk()->assertSee('Estimasi kompos')->assertSee('Login Google')->assertDontSee((string) config('services.google.client_secret') ?: 'tidak-ada-secret-x');
    }

    public function test_petugas_dashboard_is_operational_with_emerald_identity(): void
    {
        $this->actingAs($this->petugas)->get('/admin')->assertOk()
            ->assertSee('Dashboard Operasional')->assertSee('PETUGAS')->assertSee('#059669')
            ->assertSee('Aktivitas Menunggu Persetujuan')->assertSee('Nasabah teratas')->assertDontSee('Bank Sampah teratas')
            ->assertDontSee('Control Center EcoWin');
    }

    public function test_petugas_menu_and_direct_urls_for_admin_resources_are_blocked(): void
    {
        $this->actingAs($this->petugas);

        foreach ([UserResource::class, BankSampahResource::class, KategoriSampahResource::class, JenisSampahResource::class, HargaSampahResource::class, AuditLogResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertForbidden();
        }

        $this->get(HargaSampahResource::getUrl('create'))->assertForbidden();
        $this->get('/admin/pengaturan')->assertForbidden();

        $html = $this->get('/admin')->getContent();
        $this->assertStringNotContainsString('Audit Log', $html);
        $this->assertStringNotContainsString('Harga Sampah', $html);
        $this->assertStringNotContainsString('Pengguna &amp; Petugas', $html);
    }

    public function test_petugas_can_open_operational_pages(): void
    {
        $this->actingAs($this->petugas);

        foreach ([NasabahResource::class, TransaksiAnorganikResource::class, TransaksiOrganikResource::class, AktivitasBioporiResource::class,
            TitikBioporiResource::class, PenarikanSaldoResource::class, KoreksiTransaksiResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        $this->get('/admin/laporan')->assertOk();
    }

    public function test_petugas_cannot_open_other_bank_record_by_url(): void
    {
        $nasabahB = Nasabah::factory()->forBank($this->bankB)->create();
        $nasabahA = Nasabah::factory()->forBank($this->bankA)->create();

        $this->actingAs($this->petugas);
        $this->get(NasabahResource::getUrl('edit', ['record' => $nasabahB]))->assertNotFound();
        $this->get(NasabahResource::getUrl('edit', ['record' => $nasabahA]))->assertOk();
        $this->get(route('admin.nasabah.kartu', $nasabahB))->assertForbidden();
        $this->getJson(route('admin.scan-kartu.lookup', $nasabahB->kartu_qr_token))->assertNotFound();
        $this->getJson(route('admin.scan-kartu.lookup', $nasabahA->kartu_qr_token))->assertOk();
        $this->get(route('admin.berkas.identitas', $nasabahB))->assertForbidden();
    }

    public function test_audit_log_has_no_create_or_edit_pages(): void
    {
        $this->actingAs($this->admin);
        $log = AuditLog::query()->firstOrFail();

        $this->get(AuditLogResource::getUrl('view', ['record' => $log]))->assertOk();
        $this->get('/admin/audit-logs/create')->assertNotFound();
        $this->get("/admin/audit-logs/{$log->id}/edit")->assertNotFound();
    }

    public function test_transactions_cannot_be_edited_freely(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(TransaksiAnorganikResource::hasPage('edit'));
        $this->assertFalse(TransaksiOrganikResource::hasPage('edit'));
        $this->assertFalse(PenarikanSaldoResource::hasPage('edit'));
    }

    public function test_nasabah_account_cannot_access_panel(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->actingAs($nasabah->user)->get('/admin')->assertForbidden();
    }

    public function test_petugas_without_bank_cannot_access_panel(): void
    {
        $petugas = User::factory()->petugas()->create();
        $petugas->forceFill(['bank_sampah_id' => null])->save();

        $this->actingAs($petugas)->get('/admin')->assertForbidden();
    }
}
