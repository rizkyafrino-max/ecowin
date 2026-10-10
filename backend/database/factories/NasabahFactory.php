<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Nasabah>
 */
class NasabahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'no_hp' => '08'.fake()->unique()->numerify('##########'),
            'alamat_rt_rw' => 'RT 01 / RW 05',
            'nisn_atau_nik' => fake()->numerify('3374############'),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Nasabah $nasabah): void {
            $bankId = $nasabah->bank_sampah_id ?? BankSampah::factory()->create()->id;
            $admin = User::query()->where('role', 'admin')->first() ?? User::factory()->admin()->create();

            $user = new User;
            $user->forceFill([
                'nama' => $nasabah->nama,
                'email' => Str::slug($nasabah->nama, '.').'.'.Str::lower(Str::random(5)).'@gmail.com',
                'role' => Role::Nasabah->value,
                'status' => User::STATUS_AKTIF,
                'bank_sampah_id' => $bankId,
            ])->save();

            $nasabah->forceFill([
                'user_id' => $nasabah->user_id ?? $user->id,
                'bank_sampah_id' => $bankId,
                'kartu_qr_token' => Str::random(48),
                'status_verifikasi' => 'verified',
                'status' => $nasabah->status ?? 'aktif',
                'saldo' => $nasabah->saldo ?? 0,
                'dibuat_oleh' => $admin->id,
            ]);
        })->afterCreating(function (Nasabah $nasabah): void {
            $nasabah->forceFill(['nomor_nasabah' => 'NSB-'.str_pad((string) $nasabah->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function forBank(BankSampah $bank): static
    {
        return $this->afterMaking(fn (Nasabah $n) => $n->forceFill(['bank_sampah_id' => $bank->id]));
    }
}
