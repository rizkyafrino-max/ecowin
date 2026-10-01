<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $bank = BankSampah::first();
        User::updateOrCreate(['email' => 'admin@ecowin.test'], ['nama' => 'Admin EcoWin', 'password' => 'password123', 'role' => 'admin', 'bank_sampah_id' => null]);
        User::updateOrCreate(['email' => 'petugas@ecowin.test'], ['nama' => 'Petugas Satu', 'password' => 'password123', 'role' => 'petugas', 'bank_sampah_id' => $bank?->id]);
    }
}
