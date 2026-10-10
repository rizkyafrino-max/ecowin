<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setoran sampah organik. Tidak dikonversi menjadi saldo rupiah.
 */
class TransaksiOrganik extends Model
{
    use HasFactory, VisibleToUser;

    public const METODE = [
        'komposter' => 'Komposter',
        'biopori' => 'Biopori',
        'takakura' => 'Takakura',
        'maggot' => 'Maggot BSF',
    ];

    public const STATUS_PENGOLAHAN = [
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
    ];

    protected $table = 'transaksi_organik';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'berat_kg' => 'decimal:2',
            'estimasi_kompos_kg' => 'decimal:2',
            'checklist_bebas_plastik' => 'boolean',
            'checklist_bebas_logam' => 'boolean',
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

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
