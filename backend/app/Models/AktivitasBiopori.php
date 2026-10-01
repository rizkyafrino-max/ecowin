<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AktivitasBiopori extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_biopori';

    protected $fillable = [
        'nasabah_id',
        'bank_sampah_id',
        'tanggal_pemasukan',
        'berat_kg',
        'deskripsi',
        'foto_bukti_path',
        'status',
        'diperiksa_oleh',
        'waktu_diperiksa',
        'catatan_petugas',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pemasukan' => 'datetime',
            'waktu_diperiksa' => 'datetime',
            'berat_kg' => 'decimal:2',
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

    public function pemeriksa()
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    // Scope hanya status menunggu
    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    // Scope per bank sampah
    public function scopeUntukBankSampah($query, int $bankSampahId)
    {
        return $query->where('bank_sampah_id', $bankSampahId);
    }
}
