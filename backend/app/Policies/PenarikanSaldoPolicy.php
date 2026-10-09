<?php

namespace App\Policies;

use App\Models\PenarikanSaldo;
use App\Models\User;

class PenarikanSaldoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, PenarikanSaldo $penarikan): bool
    {
        if ($user->isNasabah()) {
            return $user->isActive() && (int) $user->nasabah?->id === (int) $penarikan->nasabah_id;
        }

        return $user->canManageBankSampah($penarikan->bank_sampah_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function process(User $user, PenarikanSaldo $penarikan): bool
    {
        return $user->isStaff() && $user->canManageBankSampah($penarikan->bank_sampah_id);
    }

    public function update(User $user, PenarikanSaldo $penarikan): bool
    {
        return false;
    }

    public function delete(User $user, PenarikanSaldo $penarikan): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
