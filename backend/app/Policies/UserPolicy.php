<?php

namespace App\Policies;

use App\Models\User;

/**
 * Hanya Admin yang mengelola pengguna; role tidak pernah ditentukan pengguna sendiri.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->is($model));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /** Hapus data (opsional): hanya Admin, hanya akun petugas yang sudah tidak aktif, bukan diri sendiri. */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isActive() && $model->isPetugas() && ! $model->isActive() && ! $user->is($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
