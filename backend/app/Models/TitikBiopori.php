<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TitikBiopori extends Model
{
    use HasFactory;

    protected $table = 'titik_biopori';

    protected $fillable = [
        'nasabah_id',
        'alamat_rt_rw',
        'deskripsi_lokasi',
        'latitude',
        'longitude',
        'tanggal_tanam',
        'jumlah_pipa',
        'status',
        'foto_path',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tanam' => 'date',
        ];
    }

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
