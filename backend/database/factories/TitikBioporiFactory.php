<?php

namespace Database\Factories;

use App\Models\BankSampah;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitikBiopori>
 */
class TitikBioporiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lokasi' => 'Taman '.fake()->word(),
            'alamat_rt_rw' => 'RT 01 / RW 05',
            'tanggal_tanam' => now()->subMonth()->toDateString(),
            'jumlah_pipa' => 2,
            'bioporiprint' => true,
            'status' => 'aktif',
        ];
    }

    public function forBank(BankSampah $bank): static
    {
        return $this->afterMaking(fn (TitikBiopori $t) => $t->forceFill(['bank_sampah_id' => $bank->id]));
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TitikBiopori $titik): void {
            $titik->forceFill([
                'bank_sampah_id' => $titik->bank_sampah_id ?? BankSampah::factory()->create()->id,
                'dicatat_oleh' => $titik->dicatat_oleh ?? (User::query()->where('role', 'admin')->value('id') ?? User::factory()->admin()->create()->id),
            ]);
        });
    }
}
