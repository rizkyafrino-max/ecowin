<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use Illuminate\Database\Seeder;

class BankSampahSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode' => 'BS-RT01', 'nama_bank_sampah' => 'EcoWin RT 01', 'rt' => '01', 'rw' => '05'],
            ['kode' => 'BS-RT02', 'nama_bank_sampah' => 'EcoWin RT 02', 'rt' => '02', 'rw' => '05'],
            ['kode' => 'BS-RT03', 'nama_bank_sampah' => 'EcoWin RT 03', 'rt' => '03', 'rw' => '05'],
        ];

        foreach ($data as $bank) {
            BankSampah::query()->updateOrCreate(['kode' => $bank['kode']], [
                ...$bank,
                'alamat' => 'Jl. Contoh No. '.$bank['rt'],
                'kelurahan' => 'Sidorejo Lor',
                'kecamatan' => 'Sidorejo',
                'kota' => 'Salatiga',
                'status' => 'aktif',
            ]);
        }
    }
}
