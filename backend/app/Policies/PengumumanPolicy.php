<?php

namespace App\Policies;

use App\Models\Pengumuman;
use App\Models\User;

/**
 * Admin: semua pengumuman, boleh ke semua bank sampah. Petugas: hanya untuk bank sampahnya.
 * Pengumuman tidak pernah diubah; yang belum terkirim (terjadwal) boleh dibatalkan oleh pembuat/Admin.
 */
class PengumumanPolicy
{
    private function boleh(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || ($user->isPetugas() && $user->bank_sampah_id !== null));
    }

    public function viewAny(User $user): bool
    {
        return $this->boleh($user);
    }

    public function view(User $user, Pengumuman $p): bool
    {
        return $this->boleh($user) && Pengumuman::query()->visibleTo($user)->whereKey($p->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $this->boleh($user);
    }

    public function update(User $user, Pengumuman $p): bool
    {
        return false;
    }

    public function delete(User $user, Pengumuman $p): bool
    {
        return $this->view($user, $p) && ! $p->sudahTerkirim();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
