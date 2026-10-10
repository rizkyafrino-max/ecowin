<?php

namespace App\Policies;

use App\Models\Nasabah;
use App\Models\User;

class NasabahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, Nasabah $nasabah): bool
    {
        if ($user->isNasabah()) {
            return $user->isActive() && (int) $nasabah->user_id === (int) $user->id;
        }

        return $user->canManageBankSampah($nasabah->bank_sampah_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function update(User $user, Nasabah $nasabah): bool
    {
        return $user->isStaff() && $user->canManageBankSampah($nasabah->bank_sampah_id);
    }

    public function delete(User $user, Nasabah $nasabah): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
