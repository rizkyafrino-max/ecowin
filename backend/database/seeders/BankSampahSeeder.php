<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use Illuminate\Database\Seeder;

class BankSampahSeeder extends Seeder
{
    public function run(): void
    {
        BankSampah::firstOrCreate(['nama_bank_sampah' => 'EcoWin RT 01'], ['rt' => '01', 'rw' => '05', 'alamat' => 'Salatiga', 'status' => 'aktif']);
    }
}
