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
            'Botol PET' => ['Bening', 'Bokong Putih', 'Warna'],
            'Kertas' => ['HVS', 'Koran', 'Kardus'],
            'Logam' => ['Besi', 'Aluminium', 'Kaleng'],
        ];

        foreach ($data as $namaKategori => $jenisList) {
            $kategori = KategoriSampah::create(['nama_kategori' => $namaKategori]);

            foreach ($jenisList as $namaJenis) {
                JenisSampah::create([
                    'kategori_sampah_id' => $kategori->id,
                    'nama_jenis' => $namaJenis,
                ]);
            }
        }
    }
}