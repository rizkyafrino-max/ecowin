<?php

namespace App\Policies;

use App\Models\KoreksiTransaksi;
use App\Models\User;

class KoreksiTransaksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, KoreksiTransaksi $koreksi): bool
    {
        return $user->isStaff() && $user->canManageBankSampah($koreksi->bank_sampah_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function decide(User $user, KoreksiTransaksi $koreksi): bool
    {
        return $user->isStaff() && $user->isActive() && $user->canManageBankSampah($koreksi->bank_sampah_id)
            && (int) $koreksi->diajukan_oleh !== (int) $user->id;
    }

    public function update(User $user, KoreksiTransaksi $koreksi): bool
    {
        return false;
    }

    public function delete(User $user, KoreksiTransaksi $koreksi): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
