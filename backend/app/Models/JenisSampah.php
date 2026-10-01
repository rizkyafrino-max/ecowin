<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisSampah extends Model
{
    use HasFactory;

    protected $table = 'jenis_sampah';

    protected $fillable = [
        'kategori_sampah_id',
        'nama_jenis',
    ];

    // Relasi: Jenis ini milik satu Kategori
    public function kategori()
    {
        return $this->belongsTo(KategoriSampah::class, 'kategori_sampah_id');
    }

    // Relasi: satu Jenis punya banyak riwayat Harga
    public function hargaSampah()
    {
        return $this->hasMany(HargaSampah::class, 'jenis_sampah_id');
    }

    // Accessor: ambil harga AKTIF (berlaku_mulai terbaru) untuk kondisi tertentu
    public function hargaAktif($kondisi)
    {
        return $this->hargaSampah()
            ->where('kondisi', $kondisi)
            ->orderByDesc('berlaku_mulai')
            ->first();
    }
}