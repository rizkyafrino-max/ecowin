<?php

namespace Database\Factories;

use App\Models\KategoriSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriSampah>
 */
class KategoriSampahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['nama_kategori' => fake()->unique()->word(), 'tipe' => 'anorganik'];
    }
}
