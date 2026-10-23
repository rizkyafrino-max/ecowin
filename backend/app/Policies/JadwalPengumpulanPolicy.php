<?php

namespace App\Policies;

use App\Models\JadwalPengumpulan;
use App\Models\User;

/** Jadwal pengumpulan hanya dikelola Admin (Petugas dan Nasabah tidak punya akses). */
class JadwalPengumpulanPolicy
{
    private function admin(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function viewAny(User $user): bool
    {
        return $this->admin($user);
    }

    public function view(User $user, JadwalPengumpulan $j): bool
    {
        return $this->admin($user);
    }

    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user, JadwalPengumpulan $j): bool
    {
        return $this->admin($user);
    }

    public function delete(User $user, JadwalPengumpulan $j): bool
    {
        return $this->admin($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
