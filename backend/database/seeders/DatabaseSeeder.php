<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BankSampahSeeder::class, UserSeeder::class, KategoriJenisSampahSeeder::class, HargaSampahSeeder::class, NasabahSeeder::class]);
    }
}
