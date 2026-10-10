<?php

namespace App\Policies;

use App\Models\BankSampah;
use App\Models\User;

class BankSampahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function view(User $user, BankSampah $bankSampah): bool
    {
        return $user->isActive() && ($user->isAdmin() || (int) $user->bank_sampah_id === (int) $bankSampah->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function update(User $user, BankSampah $bankSampah): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function delete(User $user, BankSampah $bankSampah): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
