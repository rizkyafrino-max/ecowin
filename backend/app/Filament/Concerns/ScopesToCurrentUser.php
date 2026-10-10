<?php

namespace App\Filament\Concerns;

use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Membatasi query Filament Resource dengan scope visibleTo() model.
 * Akses langsung via URL ke ID Bank Sampah lain => 404.
 */
trait ScopesToCurrentUser
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(static::currentUser());
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Query opsi nasabah untuk Select: hanya nasabah aktif yang boleh dikelola user.
     */
    public static function scopedNasabahQuery(Builder $query): Builder
    {
        return $query->visibleTo(static::currentUser())->where('status', 'aktif')->orderBy('nama');
    }

    /**
     * Validasi ulang di server bahwa nasabah terpilih memang boleh dikelola.
     */
    public static function findManageableNasabah(mixed $id): Nasabah
    {
        return Nasabah::query()->visibleTo(static::currentUser())->whereKey($id)->firstOrFail();
    }
}
