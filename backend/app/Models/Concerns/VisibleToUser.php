<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query scope otorisasi data berbasis role:
 * - admin   : semua data
 * - petugas : hanya data bank_sampah_id miliknya
 * - nasabah : hanya data miliknya sendiri (nasabah_id)
 *
 * Dipakai oleh API, Filament Resource, widget, dan laporan sehingga
 * pembatasan dilakukan di backend, bukan sekadar menyembunyikan menu.
 */
trait VisibleToUser
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isActive()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isPetugas()) {
            return $user->bank_sampah_id
                ? $query->where($this->qualifyColumn('bank_sampah_id'), $user->bank_sampah_id)
                : $query->whereRaw('1 = 0');
        }

        $nasabahId = $user->nasabah?->id;

        return $nasabahId
            ? $query->where($this->qualifyColumn($this->nasabahOwnerColumn()), $nasabahId)
            : $query->whereRaw('1 = 0');
    }

    public function isVisibleTo(?User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    protected function nasabahOwnerColumn(): string
    {
        return 'nasabah_id';
    }
}
