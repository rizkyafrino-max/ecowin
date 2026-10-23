<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jadwal pengumpulan sampah sebuah bank sampah. Hanya Admin yang mengelola (lihat JadwalPengumpulanPolicy). */
class JadwalPengumpulan extends Model
{
    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public const PENGINGAT = [30 => '30 menit sebelumnya', 60 => '1 jam sebelumnya', 120 => '2 jam sebelumnya', 180 => '3 jam sebelumnya', 360 => '6 jam sebelumnya', 720 => '12 jam sebelumnya'];

    protected $table = 'jadwal_pengumpulan';

    protected $fillable = ['bank_sampah_id', 'hari', 'jam_mulai', 'jam_selesai', 'lokasi', 'catatan', 'pengingat_menit', 'aktif'];

    protected function casts(): array
    {
        return ['hari' => 'array', 'aktif' => 'boolean', 'pengingat_menit' => 'integer', 'terakhir_kirim_tanggal' => 'date'];
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh')->withTrashed();
    }

    /** "Senin, Kamis" */
    public function namaHari(): string
    {
        $h = collect($this->hari ?? [])->map(fn ($i) => (int) $i)->unique()->sort();

        return $h->map(fn (int $i): string => self::HARI[$i] ?? '')->filter()->implode(', ');
    }

    /** "08.00" atau "08.00–10.00" */
    public function rentangJam(): string
    {
        $f = fn (?string $t): string => $t ? substr(str_replace(':', '.', $t), 0, 5) : '';

        return $this->jam_selesai ? $f($this->jam_mulai).'–'.$f($this->jam_selesai) : $f($this->jam_mulai);
    }
}
