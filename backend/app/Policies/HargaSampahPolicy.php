<?php

namespace App\Policies;

use App\Models\HargaSampah;
use App\Models\User;

/**
 * Data master harga/kategori hanya dikelola Admin (Petugas tidak melihat menunya).
 */
class HargaSampahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function view(User $user, HargaSampah $model): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function update(User $user, HargaSampah $model): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function delete(User $user, HargaSampah $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
