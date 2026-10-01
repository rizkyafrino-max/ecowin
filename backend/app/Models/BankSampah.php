<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSampah extends Model
{
    use HasFactory;

    protected $table = 'bank_sampah';

    protected $fillable = [
        'nama_bank_sampah',
        'rt',
        'rw',
        'alamat',
        'status',
    ];

    // Satu bank sampah punya satu petugas
    public function petugas()
    {
        return $this->hasOne(User::class, 'bank_sampah_id');
    }

    // Satu bank sampah punya banyak nasabah
    public function nasabah()
    {
        return $this->hasMany(Nasabah::class, 'bank_sampah_id');
    }

    // Transaksi anorganik dari bank sampah ini
    public function transaksiAnorganik()
    {
        return $this->hasMany(TransaksiAnorganik::class, 'bank_sampah_id');
    }

    // Transaksi organik dari bank sampah ini
    public function transaksiOrganik()
    {
        return $this->hasMany(TransaksiOrganik::class, 'bank_sampah_id');
    }

    // Aktivitas biopori dari bank sampah ini
    public function aktivitasBiopori()
    {
        return $this->hasMany(AktivitasBiopori::class, 'bank_sampah_id');
    }

    // Label display: RT 01/RW 02 — Nama Bank Sampah
    public function getLabelAttribute(): string
    {
        return "RT {$this->rt}/RW {$this->rw} — {$this->nama_bank_sampah}";
    }
}
