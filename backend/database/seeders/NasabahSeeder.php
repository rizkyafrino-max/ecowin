<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NasabahSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $bank = BankSampah::first();

        if (! $admin || ! $bank) {
            return;
        }

        foreach ([['budi001', 'Budi Santoso', '081234567001'], ['siti002', 'Siti Aminah', '081234567002']] as [$username, $nama, $noHp]) {
            Nasabah::updateOrCreate(
                ['no_hp' => $noHp],
                [
                    'username' => $username,
                    'bank_sampah_id' => $bank->id,
                    'nama' => $nama,
                    'alamat_rt_rw' => 'RT 01 / RW 05',
                    'pin' => '123456',
                    'kartu_qr_token' => Str::random(48),
                    'status_verifikasi' => 'verified',
                    'dibuat_oleh' => $admin->id,
                ],
            );
        }
    }
}
