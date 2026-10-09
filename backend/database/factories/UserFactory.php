<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->userName().'@gmail.com',
            'role' => Role::Admin->value,
            'status' => User::STATUS_AKTIF,
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin->value, 'bank_sampah_id' => null]);
    }

    public function petugas(?BankSampah $bank = null): static
    {
        return $this->state(fn () => ['role' => Role::Petugas->value, 'bank_sampah_id' => $bank?->id ?? BankSampah::factory()]);
    }

    public function nonaktif(): static
    {
        return $this->state(['status' => User::STATUS_NONAKTIF]);
    }
}
