<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NasabahSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $nasabahData = [
            ['nama' => 'Budi Santoso', 'no_hp' => '081234567001', 'alamat_rt_rw' => 'RT 01 / RW 05'],
            ['nama' => 'Siti Aminah', 'no_hp' => '081234567002', 'alamat_rt_rw' => 'RT 02 / RW 05'],
            ['nama' => 'Ahmad Fauzi', 'no_hp' => '081234567003', 'alamat_rt_rw' => 'RT 03 / RW 05'],
        ];

        foreach ($nasabahData as $data) {
            Nasabah::create([
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'alamat_rt_rw' => $data['alamat_rt_rw'],
                'kartu_qr_token' => Str::random(32),
                'status_verifikasi' => 'verified',
                'dibuat_oleh' => $admin->id,
            ]);
        }
    }
}