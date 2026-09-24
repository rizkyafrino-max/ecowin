<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenarikanSaldo extends Model
{
    use HasFactory;

    protected $table = 'penarikan_saldo';

    protected $fillable = [
        'nasabah_id',
        'jumlah',
        'status',
        'diproses_oleh',
    ];

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function pemroses()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}