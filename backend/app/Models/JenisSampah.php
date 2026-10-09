<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSampah extends Model
{
    use HasFactory;

    protected $table = 'jenis_sampah';

    protected $fillable = [
        'kategori_sampah_id',
        'nama_jenis',
        'satuan',
        'status',
    ];

    protected $attributes = [
        'satuan' => 'kg',
        'status' => 'aktif',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSampah::class, 'kategori_sampah_id');
    }

    public function hargaSampah(): HasMany
    {
        return $this->hasMany(HargaSampah::class, 'jenis_sampah_id');
    }
}
