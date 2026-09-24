<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaSampah extends Model
{
    use HasFactory;

    protected $table = 'harga_sampah';

    protected $fillable = [
        'jenis_sampah_id',
        'kondisi',
        'harga_per_kg',
        'berlaku_mulai',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'datetime',
        ];
    }

    // Relasi: Harga ini milik satu Jenis sampah
    public function jenisSampah()
    {
        return $this->belongsTo(JenisSampah::class, 'jenis_sampah_id');
    }

    // Relasi: Harga ini dibuat oleh satu User (admin/petugas)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}