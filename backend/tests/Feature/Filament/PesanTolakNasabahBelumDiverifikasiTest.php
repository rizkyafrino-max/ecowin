<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\TransaksiAnorganiks\Pages\CreateTransaksiAnorganik;
use App\Filament\Resources\TransaksiOrganiks\Pages\CreateTransaksiOrganik;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\NasabahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Petugas mencoba mencatat setoran untuk nasabah yang belum diverifikasi: server menolak, dan panel harus MENAMPILKAN alasannya
 * (notifikasi merah), bukan diam tanpa pesan. Setelah diverifikasi, pencatatan berjalan normal.
 */
class PesanTolakNasabahBelumDiverifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    private Nasabah $pending;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->admin()->create();
        $bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($bank)->create();
        $this->pending = Nasabah::factory()->forBank($bank)->create();
        $this->pending->forceFill(['status_verifikasi' => 'pending'])->save();
        $this->jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 2500]);
    }

    public function test_panel_shows_a_clear_message_for_anorganik_and_saves_nothing(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiAnorganik::class)
            ->fillForm(['nasabah_id' => $this->pending->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 4])
            ->call('create')
            ->assertNotified('Transaksi belum bisa dicatat')
            ->assertNoRedirect();

        $this->assertDatabaseCount('transaksi_anorganik', 0);
        $this->assertSame(0.0, (float) $this->pending->fresh()->saldo);
    }

    public function test_panel_shows_a_clear_message_for_organik_and_saves_nothing(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiOrganik::class)
            ->fillForm([
                'nasabah_id' => $this->pending->id, 'tanggal' => now()->toDateString(), 'jenis_organik' => 'Daun', 'berat_kg' => 2,
                'metode_pengolahan' => 'komposter', 'checklist_bebas_plastik' => true, 'checklist_bebas_logam' => true,
            ])
            ->call('create')
            ->assertNotified('Transaksi belum bisa dicatat')
            ->assertNoRedirect();

        $this->assertDatabaseCount('transaksi_organik', 0);
    }

    public function test_after_verification_the_same_form_saves_normally(): void
    {
        app(NasabahService::class)->verifikasi($this->petugas, $this->pending->fresh());
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiAnorganik::class)
            ->fillForm(['nasabah_id' => $this->pending->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 4])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('transaksi_anorganik', 1);
        $this->assertSame(10000.0, (float) $this->pending->fresh()->saldo);
    }
}
