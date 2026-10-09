<?php

namespace App\Services;

use App\Models\AktivitasBiopori;
use App\Models\BankSampah;
use App\Models\MutasiSaldo;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TitikBiopori;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Laporan & statistik. Seluruh angka dihitung lewat scope visibleTo(), sehingga
 * Petugas otomatis hanya melihat Bank Sampah miliknya dan Nasabah hanya datanya sendiri.
 */
class LaporanService
{
    /**
     * @return array<string, mixed>
     */
    public function ringkasan(User $user, ?CarbonInterface $dari = null, ?CarbonInterface $sampai = null): array
    {
        $periode = function ($query) use ($dari, $sampai) {
            return $query
                ->when($dari, fn ($q) => $q->where($q->qualifyColumn('created_at'), '>=', $dari->copy()->startOfDay()))
                ->when($sampai, fn ($q) => $q->where($q->qualifyColumn('created_at'), '<=', $sampai->copy()->endOfDay()));
        };

        $anorganik = $periode(TransaksiAnorganik::query()->visibleTo($user));
        $organik = $periode(TransaksiOrganik::query()->visibleTo($user));
        $biopori = $periode(AktivitasBiopori::query()->visibleTo($user));
        $penarikan = $periode(PenarikanSaldo::query()->visibleTo($user));

        $jenisTerbanyak = (clone $anorganik)
            ->join('harga_sampah', 'harga_sampah.id', '=', 'transaksi_anorganik.harga_sampah_id')
            ->join('jenis_sampah', 'jenis_sampah.id', '=', 'harga_sampah.jenis_sampah_id')
            ->select('jenis_sampah.nama_jenis', DB::raw('SUM(transaksi_anorganik.berat_kg) as total_berat'))
            ->groupBy('jenis_sampah.nama_jenis')
            ->orderByDesc('total_berat')
            ->first();

        return [
            'anorganik' => [
                'total_berat_kg' => round((float) (clone $anorganik)->sum('berat_kg'), 2),
                'jumlah_transaksi' => (clone $anorganik)->count(),
                'total_nilai' => (int) (clone $anorganik)->sum('nilai_rupiah'),
                'transaksi_hari_ini' => TransaksiAnorganik::query()->visibleTo($user)->whereDate('created_at', today())->count(),
                'jenis_terbanyak' => $jenisTerbanyak?->nama_jenis,
            ],
            'organik' => [
                'total_berat_kg' => round((float) (clone $organik)->sum('berat_kg'), 2),
                'jumlah_aktivitas' => (clone $organik)->count(),
                'estimasi_kompos_kg' => round((float) (clone $organik)->sum('estimasi_kompos_kg'), 2),
                'jumlah_aktivitas_biopori' => (clone $biopori)->count(),
                'biopori_pending' => (clone $biopori)->where('status', AktivitasBiopori::STATUS_PENDING)->count(),
                'biopori_terverifikasi' => (clone $biopori)->where('status', AktivitasBiopori::STATUS_APPROVED)->count(),
                'biopori_aktif' => $user->isNasabah() ? null : TitikBiopori::query()->visibleTo($user)->where('status', 'aktif')->count(),
            ],
            'keuangan' => [
                'total_saldo_nasabah' => (float) Nasabah::query()->visibleTo($user)->sum('saldo'),
                'transaksi_masuk' => (float) $periode(MutasiSaldo::query()->visibleTo($user))->where('tipe', MutasiSaldo::KREDIT)->sum('jumlah'),
                'transaksi_keluar' => (float) $periode(MutasiSaldo::query()->visibleTo($user))->where('tipe', MutasiSaldo::DEBIT)->sum('jumlah'),
                'penarikan_pending' => (clone $penarikan)->where('status', PenarikanSaldo::STATUS_PENDING)->count(),
                'total_penarikan' => (int) (clone $penarikan)->whereIn('status', [PenarikanSaldo::STATUS_APPROVED, PenarikanSaldo::STATUS_COMPLETED])->sum('jumlah'),
            ],
            'umum' => [
                'total_nasabah' => $user->isNasabah() ? 1 : Nasabah::query()->visibleTo($user)->count(),
                'total_bank_sampah' => $user->isAdmin() ? BankSampah::query()->count() : null,
                'total_petugas' => $user->isAdmin() ? User::query()->where('role', 'petugas')->count() : null,
            ],
        ];
    }

    /**
     * Grafik transaksi anorganik N bulan terakhir.
     *
     * @return array{labels: list<string>, berat: list<float>, nilai: list<int>}
     */
    public function grafikBulanan(User $user, int $bulan = 6): array
    {
        $labels = [];
        $berat = [];
        $nilai = [];

        for ($i = $bulan - 1; $i >= 0; $i--) {
            $awal = now()->startOfMonth()->subMonths($i);
            $query = TransaksiAnorganik::query()->visibleTo($user)->whereBetween('created_at', [$awal, $awal->copy()->endOfMonth()]);

            $labels[] = $awal->translatedFormat('M Y');
            $berat[] = round((float) (clone $query)->sum('berat_kg'), 2);
            $nilai[] = (int) $query->sum('nilai_rupiah');
        }

        return ['labels' => $labels, 'berat' => $berat, 'nilai' => $nilai];
    }
}
