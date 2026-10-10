<?php

namespace App\Policies;

use App\Models\LaporanKendala;
use App\Models\User;

class LaporanKendalaPolicy
{
    /**
     * Menu panel hanya untuk Admin (Petugas tetap bisa melapor via API).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function view(User $user, LaporanKendala $laporan): bool
    {
        return LaporanKendala::query()->visibleTo($user)->whereKey($laporan->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, LaporanKendala $laporan): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function delete(User $user, LaporanKendala $laporan): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
