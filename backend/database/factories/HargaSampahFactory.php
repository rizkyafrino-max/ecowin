<?php

namespace Database\Factories;

use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HargaSampah>
 */
class HargaSampahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis_sampah_id' => JenisSampah::factory(),
            'kondisi' => 'Utuh',
            'minimal_berat' => 0,
            'maksimal_berat' => null,
            'harga_per_kg' => 1000,
            'berlaku_mulai' => now()->subDay(),
            'status' => 'aktif',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (HargaSampah $harga): void {
            $harga->forceFill(['dibuat_oleh' => $harga->dibuat_oleh ?? (User::query()->where('role', 'admin')->value('id') ?? User::factory()->admin()->create()->id)]);
        });
    }
}
