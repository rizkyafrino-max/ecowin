<?php

namespace App\Policies;

use App\Models\AktivitasBiopori;
use App\Models\User;

class AktivitasBioporiPolicy
{
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

    public function view(User $user, AktivitasBiopori $aktivitas): bool
    {
        return $user->bank_sampah_id === $aktivitas->bank_sampah_id;
    }

    public function verifikasi(User $user, AktivitasBiopori $aktivitas): bool
    {
        // Petugas hanya boleh verifikasi laporan dari bank sampahnya sendiri
        return $user->bank_sampah_id === $aktivitas->bank_sampah_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'petugas']);
    }
}
