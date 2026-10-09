<?php

namespace App\Policies;

use App\Models\TransaksiOrganik;
use App\Models\User;

/**
 * Transaksi tidak dapat diedit/dihapus bebas: perubahan hanya via koreksi yang disetujui Admin.
 */
class TransaksiOrganikPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->isActive();
    }

    public function view(User $user, TransaksiOrganik $transaksi): bool
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

    public function update(User $user, TransaksiOrganik $transaksi): bool
    {
        return false;
    }

    public function delete(User $user, TransaksiOrganik $transaksi): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
