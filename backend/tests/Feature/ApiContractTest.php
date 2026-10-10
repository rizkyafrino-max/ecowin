<?php

namespace Tests\Feature;

use App\Models\Nasabah;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kontrak API yang dipakai bersama oleh Web (React) dan Android (Kotlin).
 * Daftar kunci di bawah mengikuti DTO Android (ApiModels.kt) dan pemakaian Web (web/src/pages/*).
 * Bila backend mengubah/menghapus kunci ini, tes ini gagal sebelum salah satu klien rusak.
 */
class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    private function nasabahWithData(): Nasabah
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        return Nasabah::query()
            ->whereNotNull('user_id')->where('status_verifikasi', 'verified')
            ->whereHas('transaksiAnorganik')->whereHas('aktivitasBiopori')
            ->firstOrFail();
    }

    public function test_contract_used_by_web_and_android_stays_stable(): void
    {
        $nasabah = $this->nasabahWithData();
        $this->actingAsApi($nasabah->user);

        // Sesi & profil
        $this->getJson('/api/auth/me')->assertOk()->assertJsonStructure(['data' => [
            'id', 'nama', 'email', 'avatar', 'role', 'bank_sampah_id',
            'nasabah' => ['id', 'nomor_nasabah', 'nama', 'no_hp', 'alamat_rt_rw', 'saldo', 'status', 'status_verifikasi', 'bank_sampah' => ['id', 'nama', 'rt', 'rw']],
        ]]);

        // Beranda
        $this->getJson('/api/dashboard')->assertOk()->assertJsonStructure([
            'nama', 'saldo',
            'anorganik' => ['total_berat_kg', 'jumlah_transaksi', 'total_nilai'],
            'organik' => ['total_berat_kg', 'estimasi_kompos_kg', 'jumlah_aktivitas_biopori', 'biopori_pending', 'biopori_terverifikasi'],
            'transaksi_terakhir' => ['*' => ['id', 'jenis_sampah', 'berat_kg', 'harga_per_kg', 'nilai_rupiah', 'created_at']],
            'biopori_terakhir' => ['*' => ['id', 'lokasi', 'tanggal_pemasukan', 'jenis_sampah', 'berat_kg', 'status']],
        ]);
        $this->getJson('/api/dashboard/statistics?bulan=6')->assertOk()->assertJsonStructure(['labels', 'berat', 'nilai']);

        // Saldo & mutasi & QR
        $this->getJson('/api/saldo')->assertOk()->assertJsonStructure(['saldo', 'saldo_ditahan', 'saldo_tersedia', 'total_pemasukan', 'total_penarikan']);
        $this->getJson('/api/saldo/history')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'tipe', 'jumlah', 'saldo_sebelum', 'saldo_sesudah', 'keterangan', 'created_at']]]);
        $this->getJson('/api/qr')->assertOk()->assertJsonStructure(['nomor_nasabah', 'nama', 'qr_value', 'qr_png_base64']);

        // Transaksi & harga
        $this->getJson('/api/transaksi')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'jenis_sampah', 'berat_kg', 'harga_per_kg', 'nilai_rupiah', 'created_at']]]);
        $this->getJson('/api/harga')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'nama_kategori', 'jenis_sampah' => ['*' => ['id', 'nama_jenis', 'harga']]]]]);

        // Organik: setoran petugas, aktivitas BioporiPrint, lokasi
        $this->getJson('/api/organik')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'tanggal', 'jenis_organik', 'lokasi', 'metode_pengolahan', 'berat_kg', 'estimasi_kompos_kg', 'status_pengolahan']]]);
        $this->getJson('/api/biopori')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'lokasi', 'bioporiprint', 'metode_pengolahan', 'tanggal_pemasukan', 'jenis_sampah', 'berat_kg', 'status', 'foto_url']]]);
        $this->getJson('/api/organik/biopori/lokasi')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'nama_lokasi', 'latitude', 'longitude', 'bioporiprint', 'status', 'terakhir_diisi_at', 'estimasi_panen_at', 'status_panen']]]);

        // Penarikan
        $this->getJson('/api/penarikan')->assertOk()->assertJsonStructure(['data' => ['*' => ['id', 'jumlah', 'status', 'created_at']]]);
    }

    public function test_pendaftaran_publik_contract_used_by_android(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/bank-sampah/publik')->assertOk()
            ->assertJsonStructure(['data' => ['*' => ['id', 'nama', 'rt', 'rw']]]);
    }
}
