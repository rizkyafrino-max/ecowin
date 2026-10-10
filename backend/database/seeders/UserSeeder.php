<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Admin + satu petugas per Bank Sampah. Email = akun Google testing.
     */
    public function run(): void
    {
        $this->akun(env('ECOWIN_SEED_ADMIN_EMAIL', 'admin.ecowin@gmail.com'), 'Admin EcoWin', Role::Admin, null);

        BankSampah::query()->orderBy('id')->get()->each(function (BankSampah $bank, int $i): void {
            $email = $i === 0
                ? env('ECOWIN_SEED_PETUGAS_EMAIL', 'petugas.rt01.ecowin@gmail.com')
                : 'petugas.rt'.$bank->rt.'.ecowin@gmail.com';

            $this->akun($email, 'Petugas RT '.$bank->rt, Role::Petugas, $bank->id);
        });
    }

    private function akun(string $email, string $nama, Role $role, ?int $bankId): void
    {
        $user = User::query()->firstOrNew(['email' => mb_strtolower($email)]);
        $user->forceFill([
            'nama' => $nama,
            'role' => $role->value,
            'status' => User::STATUS_AKTIF,
            'bank_sampah_id' => $bankId,
        ])->save();
    }
}
