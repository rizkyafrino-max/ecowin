<?php

namespace App\Policies;

use App\Models\TransaksiAnorganik;
use App\Models\User;

class TransaksiPolicy
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

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'petugas']);
    }

    public function view(User $user, TransaksiAnorganik $transaksi): bool
    {
        return $user->bank_sampah_id === $transaksi->bank_sampah_id;
    }
}
