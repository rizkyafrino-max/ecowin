<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\TransaksiAnorganiks\Pages\CreateTransaksiAnorganik;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Support\Desimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Input berat memakai koma ("0,5"); di database tetap titik. Format "0" + angka otomatis berkoma ditangani
 * skrip peramban (lihat decimal-input-script); di sini diuji sisi server.
 */
class DecimalInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_desimal_parse_and_format(): void
    {
        $this->assertSame(0.5, Desimal::parse('0,5'));
        $this->assertSame(0.5, Desimal::parse('0.5'));
        $this->assertSame(12.25, Desimal::parse(' 12,25 '));
        $this->assertNull(Desimal::parse(''));
        $this->assertNull(Desimal::parse(null));
        $this->assertSame('0.5', Desimal::simpan('0,5'));
        $this->assertSame('12,50', Desimal::tampil('12.50'));
        $this->assertTrue(Desimal::valid('0,5'));
        $this->assertTrue(Desimal::valid('10'));
        $this->assertTrue(Desimal::valid('1,25'));
        $this->assertFalse(Desimal::valid('1,255'));
        $this->assertFalse(Desimal::valid('abc'));
        $this->assertFalse(Desimal::valid('1,2,3'));
        $this->assertFalse(Desimal::valid('-1'));
    }

    private function setupPetugas(): array
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 2000]);

        return [$petugas, $nasabah, $jenis];
    }

    public function test_panel_accepts_comma_weight_and_stores_with_dot(): void
    {
        [$petugas, $nasabah, $jenis] = $this->setupPetugas();
        $this->actingAs($petugas);

        Livewire::test(CreateTransaksiAnorganik::class)
            ->fillForm(['nasabah_id' => $nasabah->id, 'jenis_sampah_id' => $jenis->id, 'berat_kg' => '0,5'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transaksi_anorganik', ['nasabah_id' => $nasabah->id, 'berat_kg' => '0.50', 'nilai_rupiah' => 1000]);
    }

    public function test_panel_rejects_bad_weight_formats_and_ranges(): void
    {
        [$petugas, $nasabah, $jenis] = $this->setupPetugas();
        $this->actingAs($petugas);

        foreach (['abc', '1,255', '0', '0,0', '20000'] as $bad) {
            Livewire::test(CreateTransaksiAnorganik::class)
                ->fillForm(['nasabah_id' => $nasabah->id, 'jenis_sampah_id' => $jenis->id, 'berat_kg' => $bad])
                ->call('create')
                ->assertHasFormErrors(['berat_kg']);
        }

        $this->assertDatabaseCount('transaksi_anorganik', 0);
    }
}
