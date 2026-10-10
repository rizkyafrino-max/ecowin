<?php

namespace Database\Seeders;

use App\Models\JenisSampah;
use App\Models\KategoriSampah;
use Illuminate\Database\Seeder;

class KategoriJenisSampahSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Plastik', 'anorganik', ['Botol Plastik', 'Plastik Campur']],
            ['Kertas', 'anorganik', ['Kardus', 'Kertas']],
            ['Logam', 'anorganik', ['Besi', 'Aluminium']],
            ['Organik', 'organik', ['Sisa Sayur', 'Daun Kering', 'Sisa Makanan']],
        ];

        foreach ($data as [$nama, $tipe, $jenisList]) {
            $kategori = KategoriSampah::query()->updateOrCreate(['nama_kategori' => $nama], ['tipe' => $tipe]);

            foreach ($jenisList as $jenis) {
                JenisSampah::query()->updateOrCreate(
                    ['kategori_sampah_id' => $kategori->id, 'nama_jenis' => $jenis],
                    ['satuan' => 'kg', 'status' => 'aktif'],
                );
            }
        }
    }
}
