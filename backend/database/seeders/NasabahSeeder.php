<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\NasabahService;
use Illuminate\Database\Seeder;

class NasabahSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $banks = BankSampah::query()->orderBy('id')->get();

        $data = [
            [env('ECOWIN_SEED_NASABAH_EMAIL', 'nasabah.budi.ecowin@gmail.com'), 'Budi Santoso', '081234567001', 0],
            ['nasabah.siti.ecowin@gmail.com', 'Siti Aminah', '081234567002', 0],
            ['nasabah.agus.ecowin@gmail.com', 'Agus Wibowo', '081234567003', 1],
            ['nasabah.rina.ecowin@gmail.com', 'Rina Lestari', '081234567004', 2],
        ];

        foreach ($data as [$email, $nama, $noHp, $bankIndex]) {
            if (User::query()->where('email', $email)->exists() || Nasabah::query()->where('no_hp', $noHp)->exists()) {
                continue;
            }

            $bank = $banks[$bankIndex] ?? $banks->first();

            app(NasabahService::class)->daftarkan($admin, [
                'nama' => $nama,
                'email' => $email,
                'no_hp' => $noHp,
                'alamat_rt_rw' => "RT {$bank->rt} / RW {$bank->rw}",
                'bank_sampah_id' => $bank->id,
            ]);
        }
    }
}
