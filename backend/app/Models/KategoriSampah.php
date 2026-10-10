<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriSampah extends Model
{
    use HasFactory;

    public const TIPE = [
        'anorganik' => 'Anorganik',
        'organik' => 'Organik',
    ];

    protected $table = 'kategori_sampah';

    protected $fillable = [
        'nama_kategori',
        'tipe',
    ];

    public function jenisSampah(): HasMany
    {
        return $this->hasMany(JenisSampah::class, 'kategori_sampah_id');
    }
}
