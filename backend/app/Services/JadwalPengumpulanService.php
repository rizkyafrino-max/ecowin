<?php

namespace App\Services;

use App\Models\JadwalPengumpulan;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pengingat otomatis jadwal pengumpulan sampah. Dijalankan tiap menit oleh penjadwal (ecowin:kirim-jadwal-pengumpulan).
 *
 * Aturan:
 *  - Hanya jadwal aktif pada hari yang cocok, mulai X menit sebelum jam mulai.
 *  - Tidak mengingatkan lagi setelah kegiatan selesai (mis. penjadwal baru menyala siang hari).
 *  - Sekali per hari per jadwal: pengklaiman atomik lewat terakhir_kirim_tanggal, aman dijalankan paralel/berulang.
 *  - Pengingat dikirim lewat PengumumanService (nasabah aktif bank sampah itu, tercatat di riwayat dan audit).
 */
class JadwalPengumpulanService
{
    public function __construct(private PengumumanService $pengumuman) {}

    /** Mengembalikan jumlah pengingat yang dikirim. */
    public function kirimYangJatuhTempo(?Carbon $sekarang = null): int
    {
        $sekarang ??= now();
        $tanggal = $sekarang->toDateString();
        $dikirim = 0;

        JadwalPengumpulan::query()->where('aktif', true)->with('bankSampah')->orderBy('id')->get()->each(function (JadwalPengumpulan $j) use ($sekarang, $tanggal, &$dikirim): void {
            if ($j->bankSampah === null || ! in_array($sekarang->dayOfWeekIso, array_map('intval', $j->hari ?? []), true)) {
                return;
            }
            $mulai = $sekarang->copy()->setTimeFromTimeString((string) $j->jam_mulai);
            $akhir = $j->jam_selesai ? $sekarang->copy()->setTimeFromTimeString((string) $j->jam_selesai) : $mulai->copy();
            if ($sekarang->lt($mulai->copy()->subMinutes($j->pengingat_menit)) || $sekarang->gt($akhir)) {
                return;
            }

            // Klaim atomik: hanya satu proses yang berhasil mengubah baris untuk hari ini.
            $klaim = JadwalPengumpulan::query()->whereKey($j->id)
                ->where(fn ($q) => $q->whereNull('terakhir_kirim_tanggal')->orWhere('terakhir_kirim_tanggal', '<', $tanggal))
                ->update(['terakhir_kirim_tanggal' => $tanggal]);
            if ($klaim !== 1) {
                return;
            }

            $this->kirimPengumuman($j, 'Jadwal pengumpulan sampah hari ini', $this->isiHariIni($j), $j->dibuat_oleh);
            $dikirim++;
        });

        return $dikirim;
    }

    /** Dipakai Admin setelah mengubah jadwal: kabarkan jadwal terbaru ke nasabah bank sampah itu sekarang juga. */
    public function umumkanPerubahan(JadwalPengumpulan $j, User $oleh): void
    {
        $isi = 'Jadwal terbaru: setiap '.$j->namaHari().' pukul '.$j->rentangJam().($j->lokasi ? ' di '.$j->lokasi : '').'.'.($j->catatan ? ' '.$j->catatan : '');
        $this->kirimPengumuman($j, 'Perubahan jadwal pengumpulan sampah', $isi, $oleh->id);
    }

    private function isiHariIni(JadwalPengumpulan $j): string
    {
        return 'Hari ini pukul '.$j->rentangJam().($j->lokasi ? ' di '.$j->lokasi : '').'.'.($j->catatan ? ' '.$j->catatan : ' Mohon sampah sudah dipilah.');
    }

    private function kirimPengumuman(JadwalPengumpulan $j, string $judul, string $isi, int $pembuatId): void
    {
        $p = new Pengumuman;
        $p->forceFill([
            'dibuat_oleh' => $pembuatId,
            'bank_sampah_id' => $j->bank_sampah_id,
            'jadwal_pengumpulan_id' => $j->id,
            'judul' => Str::limit($judul, 120, ''),
            'isi' => Str::limit($isi, 300, '…'),
            'kirim_pada' => now(),
        ])->save();

        $this->pengumuman->kirim($p->id);
    }
}
