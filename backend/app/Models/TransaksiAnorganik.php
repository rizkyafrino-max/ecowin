<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiAnorganik extends Model
{
    use HasFactory;

    protected $table = 'transaksi_anorganik';

    protected $fillable = [
        'nasabah_id',
        'bank_sampah_id',
        'harga_sampah_id',
        'berat_kg',
        'nilai_rupiah',
        'dicatat_oleh',
        'foto_dokumentasi_path',
    ];

    protected function casts(): array
    {
        return [
            'berat_kg' => 'decimal:2',
            'nilai_rupiah' => 'integer',
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

    public function hargaSampah()
    {
        return $this->belongsTo(HargaSampah::class, 'harga_sampah_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    // Scope per bank sampah
    public function scopeUntukBankSampah($query, int $bankSampahId)
    {
        return $query->where('bank_sampah_id', $bankSampahId);
    }
}
