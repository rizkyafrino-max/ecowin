<?php

namespace Tests\Feature;

use App\Filament\Resources\Pengumumans\Pages\CreatePengumuman;
use App\Filament\Resources\Pengumumans\Pages\ListPengumumans;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\User;
use App\Services\PengumumanService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Pengumuman Admin/Petugas ke nasabah (mis. jadwal pengumpulan sampah) lewat notifikasi dalam aplikasi. */
class PengumumanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private BankSampah $bankA;

    private BankSampah $bankB;

    private Nasabah $nasabahA;

    private Nasabah $nasabahB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create();
        $this->bankB = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bankA)->create();
        $this->nasabahA = Nasabah::factory()->forBank($this->bankA)->create();
        $this->nasabahB = Nasabah::factory()->forBank($this->bankB)->create();
    }

    private function jumlah(Nasabah $n): int
    {
        return Notifikasi::query()->where('user_id', $n->user_id)->where('tipe', 'pengumuman')->count();
    }

    public function test_petugas_mengirim_hanya_ke_nasabah_aktif_bank_sendiri_walau_formulir_dimanipulasi(): void
    {
        $nonaktif = Nasabah::factory()->forBank($this->bankA)->create(['status' => 'nonaktif']);

        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)
            ->fillForm(['judul' => 'Jadwal pengumpulan', 'isi' => 'Hari ini pukul 08.00 di Pos RW 05.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, $this->jumlah($this->nasabahA));
        $this->assertSame(0, $this->jumlah($this->nasabahB));
        $this->assertSame(0, $this->jumlah($nonaktif));

        $p = Pengumuman::query()->firstOrFail();
        $this->assertSame($this->bankA->id, $p->bank_sampah_id);
        $this->assertSame(1, $p->jumlah_penerima);
        $this->assertNotNull($p->terkirim_at);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'kirim_pengumuman', 'user_id' => $this->petugas->id]);
    }

    public function test_petugas_tidak_bisa_memilih_bank_lain_lewat_payload(): void
    {
        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)
            ->fillForm(['judul' => 'X', 'isi' => 'Y', 'bank_sampah_id' => $this->bankB->id])
            ->call('create');

        $this->assertSame($this->bankA->id, Pengumuman::query()->firstOrFail()->bank_sampah_id);
        $this->assertSame(0, $this->jumlah($this->nasabahB));
    }

    public function test_admin_bisa_ke_semua_bank_sampah(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreatePengumuman::class)
            ->fillForm(['judul' => 'Libur', 'isi' => 'Besok tidak ada pengumpulan.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, $this->jumlah($this->nasabahA));
        $this->assertSame(1, $this->jumlah($this->nasabahB));
        $this->assertNull(Pengumuman::query()->firstOrFail()->bank_sampah_id);
    }

    public function test_pengumuman_terjadwal_dikirim_saat_waktunya_dan_tidak_pernah_dua_kali(): void
    {
        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)
            ->fillForm(['judul' => 'Besok', 'isi' => 'Pengumpulan besok.', 'kirim_pada' => now()->addHours(3)->format('Y-m-d H:i:s')])
            ->call('create');

        $this->assertSame(0, $this->jumlah($this->nasabahA));
        $this->assertNull(Pengumuman::query()->firstOrFail()->terkirim_at);

        $this->artisan('ecowin:kirim-pengumuman')->assertSuccessful();
        $this->assertSame(0, $this->jumlah($this->nasabahA), 'belum waktunya');

        $this->travel(4)->hours();
        $this->artisan('ecowin:kirim-pengumuman')->assertSuccessful();
        $this->artisan('ecowin:kirim-pengumuman')->assertSuccessful();
        $this->assertSame(1, $this->jumlah($this->nasabahA), 'terkirim tepat sekali');
    }

    public function test_pengumuman_terjadwal_bisa_dibatalkan_tapi_yang_terkirim_tidak(): void
    {
        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => 'Nanti', 'isi' => 'Isi', 'kirim_pada' => now()->addDay()->format('Y-m-d H:i:s')])->call('create');
        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => 'Sekarang', 'isi' => 'Isi'])->call('create');

        $terjadwal = Pengumuman::query()->where('judul', 'Nanti')->firstOrFail();
        $terkirim = Pengumuman::query()->where('judul', 'Sekarang')->firstOrFail();

        $this->assertTrue($this->petugas->can('delete', $terjadwal));
        $this->assertFalse($this->petugas->can('delete', $terkirim));
        $this->assertFalse($this->admin->can('update', $terkirim));

        Livewire::test(ListPengumumans::class)->callAction(TestAction::make('delete')->table($terjadwal));
        $this->assertDatabaseMissing('pengumuman', ['id' => $terjadwal->id]);
    }

    public function test_petugas_tidak_melihat_pengumuman_bank_lain_dan_nasabah_tidak_punya_akses(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => 'Khusus B', 'isi' => 'Isi', 'bank_sampah_id' => $this->bankB->id])->call('create');
        $p = Pengumuman::query()->firstOrFail();

        $this->assertFalse($this->petugas->can('view', $p));
        $this->assertSame(0, Pengumuman::query()->visibleTo($this->petugas)->count());
        $this->assertFalse($this->nasabahA->user->can('viewAny', Pengumuman::class));
        $this->assertFalse($this->nasabahA->user->can('create', Pengumuman::class));
        $tanpaBank = User::factory()->petugas()->create(['bank_sampah_id' => null]);
        $this->assertFalse($tanpaBank->can('create', Pengumuman::class));
    }

    public function test_batas_harian_dan_pembersihan_html(): void
    {
        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => '<b>Halo</b><script>x()</script>', 'isi' => '<img src=x onerror=1>Isi aman'])->call('create');
        $p = Pengumuman::query()->firstOrFail();
        $this->assertStringNotContainsString('<', $p->judul.$p->isi);

        for ($i = 0; $i < PengumumanService::BATAS_HARIAN - 1; $i++) {
            Livewire::test(CreatePengumuman::class)->fillForm(['judul' => "J$i", 'isi' => 'I'])->call('create');
        }
        $this->assertSame(PengumumanService::BATAS_HARIAN, Pengumuman::query()->count());

        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => 'Lewat batas', 'isi' => 'I'])->call('create');
        $this->assertSame(PengumumanService::BATAS_HARIAN, Pengumuman::query()->count());
    }

    public function test_nasabah_menerima_pengumuman_lewat_api_notifikasi(): void
    {
        $this->actingAs($this->petugas);
        Livewire::test(CreatePengumuman::class)->fillForm(['judul' => 'Jadwal', 'isi' => 'Hari ini 08.00.'])->call('create');

        $this->actingAsApi($this->nasabahA->user)->getJson('/api/notifikasi')->assertOk()->assertJsonPath('data.0.tipe', 'pengumuman')->assertJsonPath('data.0.judul', 'Jadwal')->assertJsonPath('belum_dibaca', 1);
    }
}
