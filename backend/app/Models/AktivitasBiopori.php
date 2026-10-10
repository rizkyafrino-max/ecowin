<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan nasabah memasukkan sampah organik ke lubang Biopori (BioporiPrint).
 * Bagian dari jalur Organik. Status awal selalu pending dan harus diverifikasi petugas.
 */
class AktivitasBiopori extends Model
{
    use HasFactory, VisibleToUser;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public const METODE = [
        'biopori' => 'Biopori',
        'bioporiprint' => 'BioporiPrint',
        'lainnya' => 'Metode organik lainnya',
    ];

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_COMPLETED => 'Completed',
    ];

    protected $table = 'aktivitas_biopori';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'tanggal_pemasukan' => 'datetime',
            'waktu_diperiksa' => 'datetime',
            'berat_kg' => 'decimal:2',
            'data_awal' => 'array',
        ];
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function titikBiopori(): BelongsTo
    {
        return $this->belongsTo(TitikBiopori::class, 'titik_biopori_id');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
