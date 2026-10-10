<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKendala extends Model
{
    use HasFactory;

    protected $table = 'laporan_kendala';

    protected $fillable = [
        'kategori',
        'deskripsi',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isActive()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isPetugas()) {
            return $query->where('bank_sampah_id', $user->bank_sampah_id);
        }

        return $query->where('dilaporkan_oleh_nasabah_id', $user->nasabah?->id ?? 0);
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function pelaporNasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'dilaporkan_oleh_nasabah_id');
    }

    public function pelaporUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh_user_id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }
}
