<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSampah extends Model
{
    use HasFactory;

    protected $table = 'kategori_sampah';

    protected $fillable = [
        'nama_kategori',
    ];

    // Relasi: satu Kategori punya banyak Jenis sampah
    public function jenisSampah()
    {
        return $this->hasMany(JenisSampah::class, 'kategori_sampah_id');
    }
}