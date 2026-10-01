<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Nasabah extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nasabah';

    protected $fillable = [
        'bank_sampah_id',
<<<<<<< HEAD
        'username',
=======
>>>>>>> a3b4c50838bdd51191f54f8122435bf578fedcae
        'nama',
        'no_hp',
        'alamat_rt_rw',
        'nisn_atau_nik',
        'pin',
        'kartu_qr_token',
        'foto_ktp_kk_path',
        'status_verifikasi',
        'dibuat_oleh',
    ];

    protected $hidden = [
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
        ];
    }

    // Relasi ke bank sampah
    public function bankSampah()
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    // Nasabah dibuat oleh satu User (admin/petugas)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Transaksi anorganik milik nasabah ini
    public function transaksiAnorganik()
    {
        return $this->hasMany(TransaksiAnorganik::class, 'nasabah_id');
    }

    // Transaksi organik milik nasabah ini
    public function transaksiOrganik()
    {
        return $this->hasMany(TransaksiOrganik::class, 'nasabah_id');
    }

    // Aktivitas biopori milik nasabah ini
    public function aktivitasBiopori()
    {
        return $this->hasMany(AktivitasBiopori::class, 'nasabah_id');
    }

    // Accessor: hitung saldo dari transaksi anorganik dikurangi penarikan yang selesai
    public function getSaldoAttribute(): float
    {
        $totalMasuk = $this->transaksiAnorganik()->sum('nilai_rupiah');
        $totalKeluar = $this->hasMany(PenarikanSaldo::class, 'nasabah_id')
            ->where('status', 'selesai')
            ->sum('jumlah');

        return $totalMasuk - $totalKeluar;
    }
}
