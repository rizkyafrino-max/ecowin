<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiOrganik extends Model
{
    use HasFactory;

    protected $table = 'transaksi_organik';

    protected $fillable = [
        'nasabah_id',
        'bank_sampah_id',
        'jenis_organik',
        'berat_kg',
        'checklist_bebas_plastik',
        'checklist_bebas_logam',
        'estimasi_kompos_kg',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'berat_kg' => 'decimal:2',
            'estimasi_kompos_kg' => 'decimal:2',
            'checklist_bebas_plastik' => 'boolean',
            'checklist_bebas_logam' => 'boolean',
        ];
    }

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function bankSampah()
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function scopeUntukBankSampah($query, int $bankSampahId)
    {
        return $query->where('bank_sampah_id', $bankSampahId);
    }
}
