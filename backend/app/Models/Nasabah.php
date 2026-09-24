<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nasabah extends Model
{
    use HasFactory;

    protected $table = 'nasabah';

    protected $fillable = [
        'nama',
        'no_hp',
        'alamat_rt_rw',
        'nisn_atau_nik',
        'kartu_qr_token',
        'foto_ktp_kk_path',
        'status_verifikasi',
        'dibuat_oleh',
    ];

    // Relasi: Nasabah dibuat oleh satu User (admin/petugas)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Relasi: satu Nasabah punya banyak transaksi anorganik
    public function transaksiAnorganik()
    {
        return $this->hasMany(TransaksiAnorganik::class, 'nasabah_id');
    }

    // Relasi: satu Nasabah punya banyak transaksi organik
    public function transaksiOrganik()
    {
        return $this->hasMany(TransaksiOrganik::class, 'nasabah_id');
    }

    // Accessor: hitung saldo dari transaksi anorganik dikurangi penarikan yang selesai
    public function getSaldoAttribute()
    {
        $totalMasuk = $this->transaksiAnorganik()->sum('nilai_rupiah');
        $totalKeluar = $this->hasMany(PenarikanSaldo::class, 'nasabah_id')
            ->where('status', 'selesai')
            ->sum('jumlah');

        return $totalMasuk - $totalKeluar;
    }
}