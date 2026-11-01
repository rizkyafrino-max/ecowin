<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Kartu Nasabah di panel (admin/petugas): desain sama dengan kartu di web dan Android, dengan opsi cetak dan unduh.
 */
class KartuNasabahPanelTest extends TestCase
{
    use RefreshDatabase;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $bank = BankSampah::factory()->create(['nama_bank_sampah' => 'EcoWin RT 01', 'rt' => '01', 'rw' => '05']);
        $this->nasabah = Nasabah::factory()->forBank($bank)->create(['nama' => 'Siti Rahma']);
    }

    public function test_card_shows_identity_bank_and_a_large_qr(): void
    {
        $admin = User::factory()->admin()->create();

        $respons = $this->actingAs($admin)->get(route('admin.nasabah.kartu', $this->nasabah))->assertOk();

        $respons->assertSee('Siti Rahma')
            ->assertSee($this->nasabah->nomor_nasabah)
            ->assertSee('EcoWin RT 01')
            ->assertSee('RT 01/RW 05')
            ->assertSee('KARTU NASABAH')
            ->assertSee('PINDAI OLEH PETUGAS')
            ->assertSee('aria-label="Kartu Nasabah"', false);

        // QR berupa PNG data URI, cukup besar untuk dicetak (600 px) dan tidak memuat data pribadi di dalam kodenya.
        preg_match('/data-qr="data:image\/png;base64,([^"]+)"/', $respons->getContent(), $cocok);
        $this->assertNotEmpty($cocok[1] ?? null, 'data-qr PNG tidak ditemukan');
        $info = getimagesizefromstring(base64_decode($cocok[1]));
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThanOrEqual(600, $info[0]);
    }

    public function test_card_offers_print_and_both_downloads(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.nasabah.kartu', $this->nasabah))
            ->assertOk()
            ->assertSee('Cetak kartu')
            ->assertSee('Unduh kartu (PNG)')
            ->assertSee('Unduh QR saja')
            ->assertSee('download="QR-'.$this->nasabah->nomor_nasabah.'.png"', false)
            ->assertSee('js/kartu-png.js', false)
            // cetak memakai ukuran kartu bank sebenarnya
            ->assertSee('85.6mm', false);
    }

    public function test_the_png_renderer_script_is_served_and_exposes_its_api(): void
    {
        $this->assertFileExists(public_path('js/kartu-png.js'));
        $isi = (string) file_get_contents(public_path('js/kartu-png.js'));

        foreach (['window.buatKartuPng', 'window.unduhBlob', 'window.namaBerkasKartu', 'PINDAI OLEH PETUGAS', 'KARTU NASABAH'] as $penanda) {
            $this->assertStringContainsString($penanda, $isi);
        }
    }

    public function test_a_hostile_name_is_escaped_everywhere_including_data_attributes(): void
    {
        $nasabah = Nasabah::factory()->forBank($this->nasabah->bankSampah)->create(['nama' => '"><script>alert(1)</script>']);

        $isi = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.nasabah.kartu', $nasabah))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $isi);
        $this->assertStringContainsString('&lt;script&gt;', $isi);
        $this->assertStringContainsString('data-nama="&quot;&gt;&lt;script&gt;', $isi);
    }

    public function test_access_stays_limited_to_staff_who_may_manage_the_nasabah(): void
    {
        $this->actingAs(User::factory()->petugas($this->nasabah->bankSampah)->create())
            ->get(route('admin.nasabah.kartu', $this->nasabah))->assertOk();

        $this->actingAs(User::factory()->petugas(BankSampah::factory()->create())->create())
            ->get(route('admin.nasabah.kartu', $this->nasabah))->assertForbidden();

        $this->actingAs($this->nasabah->user)
            ->get(route('admin.nasabah.kartu', $this->nasabah))->assertForbidden();
    }
}
