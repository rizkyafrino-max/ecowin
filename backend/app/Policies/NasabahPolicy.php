<?php

namespace App\Policies;

use App\Models\Nasabah;
use App\Models\User;

class NasabahPolicy
{
    /**
     * Admin dapat melakukan apa saja.
     * Petugas hanya untuk nasabah di bank sampahnya.
     */
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'petugas']);
    }

    public function view(User $user, Nasabah $nasabah): bool
    {
        return $user->bank_sampah_id === $nasabah->bank_sampah_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'petugas']);
    }

    public function update(User $user, Nasabah $nasabah): bool
    {
        return $user->bank_sampah_id === $nasabah->bank_sampah_id;
    }

    public function delete(User $user, Nasabah $nasabah): bool
    {
        return false; // nasabah tidak boleh dihapus
    }

    public function verifikasi(User $user, Nasabah $nasabah): bool
    {
        return $user->bank_sampah_id === $nasabah->bank_sampah_id;
    }

    public function resetPin(User $user, Nasabah $nasabah): bool
    {
        return $user->bank_sampah_id === $nasabah->bank_sampah_id;
    }
}
