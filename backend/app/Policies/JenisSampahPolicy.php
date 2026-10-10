<?php

namespace App\Policies;

use App\Models\JenisSampah;
use App\Models\User;

/**
 * Data master harga/kategori hanya dikelola Admin (Petugas tidak melihat menunya).
 */
class JenisSampahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function view(User $user, JenisSampah $model): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function update(User $user, JenisSampah $model): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function delete(User $user, JenisSampah $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
