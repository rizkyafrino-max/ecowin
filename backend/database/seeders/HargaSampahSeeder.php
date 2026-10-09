<?php

namespace Database\Seeders;

use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\User;
use Illuminate\Database\Seeder;

class HargaSampahSeeder extends Seeder
{
    /**
     * Harga bertingkat contoh: 0–5 kg, 5–10 kg, > 10 kg.
     */
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $harga = [
            'Botol Plastik' => [3000, 3300, 3600],
            'Plastik Campur' => [1000, 1100, 1200],
            'Kardus' => [1800, 2000, 2200],
            'Kertas' => [1500, 1700, 1900],
            'Besi' => [3500, 3800, 4100],
            'Aluminium' => [12000, 12500, 13000],
        ];

        foreach ($harga as $namaJenis => [$tier1, $tier2, $tier3]) {
            $jenis = JenisSampah::query()->where('nama_jenis', $namaJenis)->first();

            if (! $jenis || $jenis->hargaSampah()->exists()) {
                continue;
            }

            foreach ([[0, 5, $tier1], [5, 10, $tier2], [10, null, $tier3]] as [$min, $max, $rp]) {
                $row = new HargaSampah([
                    'jenis_sampah_id' => $jenis->id,
                    'kondisi' => 'Utuh',
                    'minimal_berat' => $min,
                    'maksimal_berat' => $max,
                    'harga_per_kg' => $rp,
                    'berlaku_mulai' => now()->startOfDay(),
                    'status' => 'aktif',
                ]);
                $row->forceFill(['dibuat_oleh' => $admin->id])->save();
            }
        }
    }
}
