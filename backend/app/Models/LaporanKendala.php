<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanKendala extends Model
{
    use HasFactory;

    protected $table = 'laporan_kendala';

    protected $fillable = [
        'dilaporkan_oleh_nasabah_id',
        'dilaporkan_oleh_user_id',
        'kategori',
        'deskripsi',
        'status',
        'ditinjau_oleh',
        'catatan_admin',
    ];

    public function pelaporNasabah()
    {
        return $this->belongsTo(Nasabah::class, 'dilaporkan_oleh_nasabah_id');
    }

    public function pelaporUser()
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh_user_id');
    }

    public function peninjau()
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }
}