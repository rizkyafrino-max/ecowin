<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KoreksiTransaksi extends Model
{
    use HasFactory;

    protected $table = 'koreksi_transaksi';

    protected $fillable = [
        'transaksi_id',
        'tipe_transaksi',
        'diajukan_oleh',
        'disetujui_oleh',
        'status',
        'alasan',
    ];

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    // Ambil data transaksi asli (anorganik atau organik), sesuai tipe_transaksi
    public function transaksiAsli()
    {
        if ($this->tipe_transaksi === 'anorganik') {
            return TransaksiAnorganik::find($this->transaksi_id);
        }

        return TransaksiOrganik::find($this->transaksi_id);
    }
}