<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil nasabah. Login dilakukan oleh akun User (role nasabah) via Google;
 * tidak ada PIN. Saldo hanya boleh berubah melalui SaldoService (mutasi_saldo).
 */
class Nasabah extends Model
{
    use HasFactory, VisibleToUser;

    protected $table = 'nasabah';

    protected $fillable = [
        'nama',
        'no_hp',
        'alamat_rt_rw',
        'nisn_atau_nik',
        'foto_ktp_kk_path',
    ];

    protected $attributes = [
        'saldo' => 0,
        'status' => 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
        ];
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isNasabah() && $user->isActive()) {
            return $query->where('user_id', $user->id);
        }

        if ($user?->isNasabah()) {
            return $query->whereRaw('1 = 0');
        }

        return $this->baseVisibleTo($query, $user);
    }

    protected function baseVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isActive()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $user->bank_sampah_id
            ? $query->where('bank_sampah_id', $user->bank_sampah_id)
            : $query->whereRaw('1 = 0');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function transaksiAnorganik(): HasMany
    {
        return $this->hasMany(TransaksiAnorganik::class, 'nasabah_id');
    }

    public function transaksiOrganik(): HasMany
    {
        return $this->hasMany(TransaksiOrganik::class, 'nasabah_id');
    }

    public function aktivitasBiopori(): HasMany
    {
        return $this->hasMany(AktivitasBiopori::class, 'nasabah_id');
    }

    public function penarikanSaldo(): HasMany
    {
        return $this->hasMany(PenarikanSaldo::class, 'nasabah_id');
    }

    public function mutasiSaldo(): HasMany
    {
        return $this->hasMany(MutasiSaldo::class, 'nasabah_id');
    }

    /**
     * Saldo yang sedang "ditahan" oleh pengajuan penarikan yang belum diputuskan.
     */
    public function saldoDitahan(): float
    {
        return (float) $this->penarikanSaldo()->where('status', PenarikanSaldo::STATUS_PENDING)->sum('jumlah');
    }

    public function saldoTersedia(): float
    {
        return (float) $this->saldo - $this->saldoDitahan();
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }
}
