<?php

namespace App\Policies;

use App\Models\TransaksiAnorganik;
use App\Models\User;

/**
 * Transaksi tidak dapat diedit/dihapus bebas: perubahan hanya via koreksi yang disetujui Admin.
 */
class TransaksiAnorganikPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, TransaksiAnorganik $transaksi): bool
    {
        if ($user->isNasabah()) {
            return $user->isActive() && (int) $user->nasabah?->id === (int) $transaksi->nasabah_id;
        }

        return $user->canManageBankSampah($transaksi->bank_sampah_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function update(User $user, TransaksiAnorganik $transaksi): bool
    {
        return false;
    }

    public function delete(User $user, TransaksiAnorganik $transaksi): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
