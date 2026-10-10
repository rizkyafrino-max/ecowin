<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setoran sampah anorganik. Dibuat hanya lewat TransaksiService (harga diambil
 * dari database, bukan dari input klien). Perubahan hanya via koreksi yang
 * disetujui Admin.
 */
class TransaksiAnorganik extends Model
{
    use HasFactory, VisibleToUser;

    protected $table = 'transaksi_anorganik';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'berat_kg' => 'decimal:2',
            'harga_per_kg' => 'decimal:2',
            'nilai_rupiah' => 'integer',
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

    public function hargaSampah(): BelongsTo
    {
        return $this->belongsTo(HargaSampah::class, 'harga_sampah_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
