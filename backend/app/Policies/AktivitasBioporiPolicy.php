<?php

namespace App\Policies;

use App\Models\AktivitasBiopori;
use App\Models\User;

class AktivitasBioporiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, AktivitasBiopori $aktivitas): bool
    {
        if ($user->isNasabah()) {
            return $user->isActive() && (int) $user->nasabah?->id === (int) $aktivitas->nasabah_id;
        }

        return $user->canManageBankSampah($aktivitas->bank_sampah_id);
    }

    /**
     * Laporan aktivitas dibuat oleh nasabah dari aplikasi Android.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function verify(User $user, AktivitasBiopori $aktivitas): bool
    {
        return $user->isStaff() && $user->canManageBankSampah($aktivitas->bank_sampah_id);
    }

    public function update(User $user, AktivitasBiopori $aktivitas): bool
    {
        return false;
    }

    public function delete(User $user, AktivitasBiopori $aktivitas): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
