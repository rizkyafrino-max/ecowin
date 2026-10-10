<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan koreksi transaksi oleh Petugas, diputuskan oleh Admin.
 */
class KoreksiTransaksi extends Model
{
    use HasFactory, VisibleToUser;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $table = 'koreksi_transaksi';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'data_koreksi' => 'array',
            'data_sebelum' => 'array',
            'diputuskan_at' => 'datetime',
        ];
    }

    /**
     * Nasabah tidak punya akses ke koreksi.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user?->isStaff() || ! $user->isActive()) {
            return $query->whereRaw('1 = 0');
        }

        return $user->isAdmin() ? $query : $query->where('bank_sampah_id', $user->bank_sampah_id);
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function transaksiAsli(): TransaksiAnorganik|TransaksiOrganik|null
    {
        return $this->tipe_transaksi === 'anorganik'
            ? TransaksiAnorganik::find($this->transaksi_id)
            : TransaksiOrganik::find($this->transaksi_id);
    }
}
