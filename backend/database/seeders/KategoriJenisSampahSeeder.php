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
            'Anorganik' => [
                'Botol PET' => ['Bening', 'Bokong Putih', 'Warna'],
                'Kertas' => ['HVS', 'Koran', 'Kardus'],
                'Logam' => ['Besi', 'Aluminium', 'Kaleng'],
            ],
            'Organik' => [
                'Organik Rumah Tangga' => ['Sisa makanan', 'Daun kering', 'Sampah kebun'],
            ],
        ];

        foreach ($data as $jalur => $kategoriData) {
            foreach ($kategoriData as $namaKategori => $jenisList) {
                $kategori = KategoriSampah::firstOrCreate(['nama_kategori' => $jalur.' · '.$namaKategori]);

                foreach ($jenisList as $namaJenis) {
                    JenisSampah::firstOrCreate([
                        'kategori_sampah_id' => $kategori->id,
                        'nama_jenis' => $namaJenis,
                    ]);
                }
            }
        }
    }
}
