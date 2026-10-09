<?php

namespace Database\Seeders;

use App\Models\AktivitasBiopori;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\TitikBiopori;
use App\Models\User;
use App\Services\BioporiService;
use App\Services\PenarikanService;
use App\Services\TransaksiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk demo/pengembangan (setoran anorganik & organik, aktivitas BioporiPrint, penarikan).
 * Dijalankan terpisah: php artisan db:seed --class=DemoDataSeeder. Ditolak di production; aman diulang
 * (tidak menambah data bila sudah pernah diisi).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder tidak boleh dijalankan di production.');

            return;
        }

        if (DB::table('transaksi_anorganik')->count() >= 20) {
            $this->command?->info('Data contoh sudah ada, dilewati.');

            return;
        }

        mt_srand(11);
        $transaksi = app(TransaksiService::class);
        $jenis = JenisSampah::query()->where('status', 'aktif')->whereHas('kategori', fn ($q) => $q->where('tipe', 'anorganik'))->get();
        $nasabahList = Nasabah::query()->where('status_verifikasi', 'verified')->where('status', 'aktif')->get();

        if ($jenis->isEmpty() || $nasabahList->isEmpty()) {
            $this->command?->warn('Jalankan db:seed biasa dulu (jenis sampah dan nasabah belum ada).');

            return;
        }

        foreach ($nasabahList as $nasabah) {
            $petugas = User::query()->where('role', 'petugas')->where('bank_sampah_id', $nasabah->bank_sampah_id)->first()
                ?? User::query()->where('role', 'admin')->first();

            // Setoran anorganik tersebar 6 bulan terakhir.
            for ($i = 0; $i < 8; $i++) {
                $trx = $transaksi->catatAnorganik($petugas, $nasabah, $jenis->random(), round(mt_rand(15, 120) / 10, 1));
                $waktu = now()->subDays(mt_rand(0, 170))->setTime(mt_rand(8, 16), mt_rand(0, 59));
                DB::table('transaksi_anorganik')->where('id', $trx->id)->update(['created_at' => $waktu, 'updated_at' => $waktu]);
            }

            // Setoran organik dicatat petugas (tanpa saldo rupiah).
            foreach (['Sisa sayur', 'Daun kering', 'Kulit buah'] as $organik) {
                $transaksi->catatOrganik($petugas, $nasabah, [
                    'jenis_organik' => $organik, 'berat_kg' => round(mt_rand(20, 80) / 10, 1),
                    'metode_pengolahan' => 'komposter', 'lokasi' => 'Komposter RT',
                    'tanggal' => now()->subDays(mt_rand(1, 60))->toDateString(),
                ]);
            }
        }

        $this->aktivitasBioporiPrint($nasabahList);
        $this->penarikan($nasabahList);

        $this->command?->info('Data contoh selesai dibuat.');
    }

    /** Aktivitas BioporiPrint dengan foto bukti kecil buatan (GD), berbagai status. */
    private function aktivitasBioporiPrint($nasabahList): void
    {
        $statuses = ['approved', 'pending', 'rejected'];
        $bioporiService = app(BioporiService::class);

        foreach ($nasabahList as $i => $nasabah) {
            $titik = TitikBiopori::query()->where('bank_sampah_id', $nasabah->bank_sampah_id)->where('bioporiprint', true)->where('status', 'aktif')->first();

            if (! $titik) {
                continue;
            }

            $petugas = User::query()->where('role', 'petugas')->where('bank_sampah_id', $nasabah->bank_sampah_id)->first();

            foreach ($statuses as $k => $status) {
                $path = 'bukti-biopori/demo-'.$nasabah->id.'-'.$k.'.jpg';
                Storage::disk('local')->put($path, $this->fotoContoh());

                $aktivitas = new AktivitasBiopori;
                $aktivitas->forceFill([
                    'nasabah_id' => $nasabah->id, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'titik_biopori_id' => $titik->id,
                    'metode_pengolahan' => 'bioporiprint', 'tanggal_pemasukan' => now()->subDays(($i + 1) * 3 + $k * 5),
                    'jenis_sampah' => ['Sisa sayur', 'Daun kering', 'Kulit buah'][$k], 'berat_kg' => round(1 + $k * 0.8, 1),
                    'deskripsi' => 'Data contoh', 'foto_bukti_path' => $path, 'status' => 'pending',
                    'data_awal' => ['titik_biopori_id' => $titik->id, 'contoh' => true],
                ])->save();

                if ($petugas && $status === 'approved') {
                    $bioporiService->setujui($petugas, $aktivitas, 'Sesuai foto');
                } elseif ($petugas && $status === 'rejected') {
                    $bioporiService->tolak($petugas, $aktivitas, 'Foto kurang jelas');
                }
            }
        }
    }

    /** Satu penarikan disetujui dan satu menunggu untuk nasabah bersaldo. */
    private function penarikan($nasabahList): void
    {
        $service = app(PenarikanService::class);
        $adaSaldo = Nasabah::query()->whereIn('id', $nasabahList->pluck('id'))->where('saldo', '>=', 30000)->get();

        foreach ($adaSaldo->take(2) as $i => $nasabah) {
            $petugas = User::query()->where('role', 'petugas')->where('bank_sampah_id', $nasabah->bank_sampah_id)->first();
            $penarikan = $service->ajukan($nasabah->user, $nasabah, 10000 * ($i + 1), 'Data contoh');

            if ($i === 0 && $petugas) {
                $service->setujui($petugas, $penarikan, 'Disetujui');
            }
        }
    }

    private function fotoContoh(): string
    {
        $img = imagecreatetruecolor(480, 320);
        imagefill($img, 0, 0, imagecolorallocate($img, 220, 245, 232));
        imagefilledellipse($img, 240, 170, 170, 170, imagecolorallocate($img, 5, 150, 105));
        imagefilledrectangle($img, 215, 60, 265, 150, imagecolorallocate($img, 4, 120, 87));
        ob_start();
        imagejpeg($img, null, 80);
        $data = (string) ob_get_clean();
        imagedestroy($img);

        return $data;
    }
}
