<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal development. Semua akun login dengan Google: ganti email di .env
     * (ECOWIN_SEED_*_EMAIL) dengan akun Google testing Anda. Tidak ada password/PIN.
     */
    public function run(): void
    {
        $this->call([
            BankSampahSeeder::class,
            UserSeeder::class,
            KategoriJenisSampahSeeder::class,
            HargaSampahSeeder::class,
            NasabahSeeder::class,
            TitikBioporiSeeder::class,
        ]);
    }
}
