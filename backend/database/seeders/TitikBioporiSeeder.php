<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Database\Seeder;

class TitikBioporiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        BankSampah::query()->get()->each(function (BankSampah $bank) use ($admin): void {
            if ($bank->titikBiopori()->exists()) {
                return;
            }

            $titik = new TitikBiopori([
                'nama_lokasi' => 'Taman RT '.$bank->rt,
                'alamat_rt_rw' => "RT {$bank->rt} / RW {$bank->rw}",
                'tanggal_tanam' => now()->subMonth()->toDateString(),
                'jumlah_pipa' => 4,
                'bioporiprint' => true,
                'status' => 'aktif',
            ]);
            $titik->forceFill(['bank_sampah_id' => $bank->id, 'dicatat_oleh' => $admin->id])->save();
        });
    }
}
