<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama' => 'Admin EcoWin',
            'email' => 'admin@ecowin.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        User::create([
            'nama' => 'Petugas Satu',
            'email' => 'petugas@ecowin.test',
            'password' => 'password123',
            'role' => 'petugas',
        ]);
    }
}