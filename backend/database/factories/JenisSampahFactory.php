<?php

namespace Database\Factories;

use App\Models\JenisSampah;
use App\Models\KategoriSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisSampah>
 */
class JenisSampahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kategori_sampah_id' => KategoriSampah::factory(),
            'nama_jenis' => fake()->unique()->word(),
            'satuan' => 'kg',
            'status' => 'aktif',
        ];
    }
}
