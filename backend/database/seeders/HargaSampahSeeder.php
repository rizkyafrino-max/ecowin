<?php

namespace Database\Seeders;

use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\User;
use Illuminate\Database\Seeder;

class HargaSampahSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            return;
        }

        $hargaData = [
            'Bening' => 4000,
            'Bokong Putih' => 3000,
            'Warna' => 2000,
            'HVS' => 2500,
            'Koran' => 1500,
            'Kardus' => 1800,
            'Besi' => 3500,
            'Aluminium' => 12000,
            'Kaleng' => 5000,
        ];

        foreach ($hargaData as $namaJenis => $harga) {
            $jenis = JenisSampah::where('nama_jenis', $namaJenis)->first();

            if ($jenis) {
                HargaSampah::firstOrCreate(
                    ['jenis_sampah_id' => $jenis->id, 'kondisi' => 'Utuh', 'berlaku_mulai' => now()->startOfMinute()],
                    ['harga_per_kg' => $harga, 'dibuat_oleh' => $admin->id],
                );
            }
        }
    }
}
