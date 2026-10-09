<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Histori perubahan saldo (append-only). Tidak dapat diubah/dihapus.
 */
class MutasiSaldo extends Model
{
    use VisibleToUser;

    public const KREDIT = 'kredit';

    public const DEBIT = 'debit';

    protected $table = 'mutasi_saldo';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'saldo_sebelum' => 'decimal:2',
            'saldo_sesudah' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Mutasi saldo tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Mutasi saldo tidak boleh dihapus.'));
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function sumber(): MorphTo
    {
        return $this->morphTo();
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
