<?php

namespace App\Policies;

use App\Models\TitikBiopori;
use App\Models\User;

class TitikBioporiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, TitikBiopori $titik): bool
    {
        if ($user->isNasabah()) {
            return $user->isActive() && (int) $user->bank_sampah_id === (int) $titik->bank_sampah_id;
        }

        return $user->canManageBankSampah($titik->bank_sampah_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function update(User $user, TitikBiopori $titik): bool
    {
        return $user->isStaff() && $user->canManageBankSampah($titik->bank_sampah_id);
    }

    public function delete(User $user, TitikBiopori $titik): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
