<?php

namespace Database\Factories;

use App\Models\BankSampah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankSampah>
 */
class BankSampahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rt = str_pad((string) fake()->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);

        return [
            'nama_bank_sampah' => 'Bank Sampah RT '.$rt,
            'kode' => 'BS-'.$rt.fake()->unique()->numberBetween(100, 999),
            'rt' => $rt,
            'rw' => '05',
            'alamat' => fake()->streetAddress(),
            'status' => 'aktif',
        ];
    }
}
