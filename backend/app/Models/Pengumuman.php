<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengumuman ke nasabah. Hanya dibuat lewat panel (PengumumanResource) dan dikirim oleh PengumumanService;
 * tidak mass-assignable dari request.
 */
class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['kirim_pada' => 'datetime', 'terkirim_at' => 'datetime'];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh')->withTrashed();
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function sudahTerkirim(): bool
    {
        return $this->terkirim_at !== null;
    }

    /** Admin melihat semua; Petugas hanya pengumuman untuk bank sampahnya. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isActive()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isPetugas() && $user->bank_sampah_id !== null) {
            return $query->where('bank_sampah_id', $user->bank_sampah_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
