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
    public function ringkasan(User $user, ?CarbonInterface $dari = null, ?CarbonInterface $sampai = null, ?int $bankId = null): array
    {
        // Filter Bank Sampah hanya berlaku untuk Admin; Petugas selalu terkunci pada Bank Sampah miliknya (visibleTo).
        $bankId = $user->isAdmin() ? $bankId : null;
        $bank = fn ($query) => $bankId ? $query->where($query->qualifyColumn('bank_sampah_id'), $bankId) : $query;

        $periode = function ($query) use ($dari, $sampai) {
            return $query
                ->when($dari, fn ($q) => $q->where($q->qualifyColumn('created_at'), '>=', $dari->copy()->startOfDay()))
                ->when($sampai, fn ($q) => $q->where($q->qualifyColumn('created_at'), '<=', $sampai->copy()->endOfDay()));
        };

        $anorganik = $periode($bank(TransaksiAnorganik::query()->visibleTo($user)));
        $organik = $periode($bank(TransaksiOrganik::query()->visibleTo($user)));
        $biopori = $periode($bank(AktivitasBiopori::query()->visibleTo($user)));
        $penarikan = $periode($bank(PenarikanSaldo::query()->visibleTo($user)));

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
                'transaksi_hari_ini' => $bank(TransaksiAnorganik::query()->visibleTo($user))->whereDate('created_at', today())->count(),
                'jenis_terbanyak' => $jenisTerbanyak?->nama_jenis,
            ],
            'organik' => [
                'total_berat_kg' => round((float) (clone $organik)->sum('berat_kg'), 2),
                'jumlah_aktivitas' => (clone $organik)->count(),
                'estimasi_kompos_kg' => round((float) (clone $organik)->sum('estimasi_kompos_kg'), 2),
                'jumlah_aktivitas_biopori' => (clone $biopori)->count(),
                'biopori_pending' => (clone $biopori)->where('status', AktivitasBiopori::STATUS_PENDING)->count(),
                'biopori_terverifikasi' => (clone $biopori)->where('status', AktivitasBiopori::STATUS_APPROVED)->count(),
                'biopori_aktif' => $user->isNasabah() ? null : $bank(TitikBiopori::query()->visibleTo($user))->disetujui()->where('status', 'aktif')->count(),
            ],
            'keuangan' => [
                'total_saldo_nasabah' => (float) $bank(Nasabah::query()->visibleTo($user))->sum('saldo'),
                'transaksi_masuk' => (float) $periode($bank(MutasiSaldo::query()->visibleTo($user)))->where('tipe', MutasiSaldo::KREDIT)->sum('jumlah'),
                'transaksi_keluar' => (float) $periode($bank(MutasiSaldo::query()->visibleTo($user)))->where('tipe', MutasiSaldo::DEBIT)->sum('jumlah'),
                'penarikan_pending' => (clone $penarikan)->where('status', PenarikanSaldo::STATUS_PENDING)->count(),
                'total_penarikan' => (int) (clone $penarikan)->whereIn('status', [PenarikanSaldo::STATUS_APPROVED, PenarikanSaldo::STATUS_COMPLETED])->sum('jumlah'),
            ],
            'umum' => [
                'total_nasabah' => $user->isNasabah() ? 1 : $bank(Nasabah::query()->visibleTo($user))->count(),
                'total_bank_sampah' => $user->isAdmin() ? BankSampah::query()->count() : null,
                'total_petugas' => $user->isAdmin() ? User::query()->where('role', 'petugas')->count() : null,
            ],
        ];
    }

    /**
     * Rekap satu baris per Bank Sampah (hanya Admin): dasar tabel "Semua Bank Sampah" pada laporan cetak.
     *
     * @return list<array<string, mixed>>
     */
    public function rekapPerBank(User $user, ?CarbonInterface $dari = null, ?CarbonInterface $sampai = null): array
    {
        if (! $user->isAdmin()) {
            return [];
        }

        return BankSampah::query()->orderBy('nama_bank_sampah')->get()->map(function (BankSampah $b) use ($user, $dari, $sampai): array {
            $r = $this->ringkasan($user, $dari, $sampai, $b->id);

            return [
                'bank' => $b->nama_bank_sampah,
                'rtrw' => 'RT '.$b->rt.' / RW '.$b->rw,
                'nasabah' => $r['umum']['total_nasabah'],
                'anorganik_kg' => $r['anorganik']['total_berat_kg'],
                'anorganik_nilai' => $r['anorganik']['total_nilai'],
                'organik_kg' => $r['organik']['total_berat_kg'],
                'penarikan' => $r['keuangan']['total_penarikan'],
                'saldo' => $r['keuangan']['total_saldo_nasabah'],
            ];
        })->all();
    }

    /**
     * Query dasar daftar rinci (anorganik | organik | penarikan) sesuai hak akses dan filter. Dipakai cetak dan ekspor.
     * Bank Sampah hanya berlaku untuk Admin; Petugas selalu terkunci lewat visibleTo().
     */
    public function kueri(User $user, string $jenis, ?CarbonInterface $dari = null, ?CarbonInterface $sampai = null, ?int $bankId = null): \Illuminate\Database\Eloquent\Builder
    {
        $bankId = $user->isAdmin() ? $bankId : null;
        $model = match ($jenis) {
            'anorganik' => TransaksiAnorganik::query()->with(['nasabah:id,nama,nomor_nasabah', 'hargaSampah.jenisSampah', 'bankSampah:id,nama_bank_sampah']),
            'organik' => TransaksiOrganik::query()->with(['nasabah:id,nama,nomor_nasabah', 'bankSampah:id,nama_bank_sampah']),
            'penarikan' => PenarikanSaldo::query()->with(['nasabah:id,nama,nomor_nasabah', 'bankSampah:id,nama_bank_sampah']),
            default => throw new \InvalidArgumentException('Jenis laporan tidak dikenal.'),
        };

        return $model->visibleTo($user)
            ->when($bankId, fn ($q) => $q->where($q->qualifyColumn('bank_sampah_id'), $bankId))
            ->when($dari, fn ($q) => $q->where($q->qualifyColumn('created_at'), '>=', $dari->copy()->startOfDay()))
            ->when($sampai, fn ($q) => $q->where($q->qualifyColumn('created_at'), '<=', $sampai->copy()->endOfDay()));
    }
    /**
     * Daftar rinci untuk laporan cetak. Setiap tabel dibatasi $batas baris terbaru agar halaman cetak tetap ringan.
     *
     * @return array{anorganik: \Illuminate\Support\Collection, organik: \Illuminate\Support\Collection, penarikan: \Illuminate\Support\Collection, batas: int, terpotong: array<string, bool>}
     */
    public function detail(User $user, ?CarbonInterface $dari = null, ?CarbonInterface $sampai = null, ?int $bankId = null, int $batas = 500): array
    {
        $bankId = $user->isAdmin() ? $bankId : null;
        $saring = function ($query) use ($dari, $sampai, $bankId) {
            return $query
                ->when($bankId, fn ($q) => $q->where($q->qualifyColumn('bank_sampah_id'), $bankId))
                ->when($dari, fn ($q) => $q->where($q->qualifyColumn('created_at'), '>=', $dari->copy()->startOfDay()))
                ->when($sampai, fn ($q) => $q->where($q->qualifyColumn('created_at'), '<=', $sampai->copy()->endOfDay()));
        };

        $anorganik = $saring(TransaksiAnorganik::query()->visibleTo($user));
        $organik = $saring(TransaksiOrganik::query()->visibleTo($user));
        $penarikan = $saring(PenarikanSaldo::query()->visibleTo($user));
        $cacah = [(clone $anorganik)->count(), (clone $organik)->count(), (clone $penarikan)->count()];

        return [
            'anorganik' => $anorganik->with(['nasabah:id,nama,nomor_nasabah', 'hargaSampah.jenisSampah', 'bankSampah:id,nama_bank_sampah'])->latest('id')->limit($batas)->get(),
            'organik' => $organik->with(['nasabah:id,nama,nomor_nasabah', 'bankSampah:id,nama_bank_sampah'])->latest('id')->limit($batas)->get(),
            'penarikan' => $penarikan->with(['nasabah:id,nama,nomor_nasabah', 'bankSampah:id,nama_bank_sampah'])->latest('id')->limit($batas)->get(),
            'batas' => $batas,
            'terpotong' => ['anorganik' => $cacah[0] > $batas, 'organik' => $cacah[1] > $batas, 'penarikan' => $cacah[2] > $batas],
        ];
    }
    /**
     * Grafik transaksi anorganik N bulan terakhir.
     *
     * @return array{labels: list<string>, berat: list<float>, nilai: list<int>}
     */
    public function grafikBulanan(User $user, int $bulan = 6): array
    {
        $awal = now()->startOfMonth()->subMonths($bulan - 1);
        // Satu query dikelompokkan per bulan (sebelumnya 2 query per bulan).
        $bln = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $baris = TransaksiAnorganik::query()->visibleTo($user)
            ->where('transaksi_anorganik.created_at', '>=', $awal)
            ->selectRaw("{$bln} as bln, SUM(berat_kg) as berat, SUM(nilai_rupiah) as nilai")
            ->groupBy('bln')->get()->keyBy('bln');

        $labels = [];
        $berat = [];
        $nilai = [];

        for ($i = 0; $i < $bulan; $i++) {
            $bulanIni = $awal->copy()->addMonths($i);
            $kunci = $bulanIni->format('Y-m');

            $labels[] = $bulanIni->translatedFormat('M Y');
            $berat[] = round((float) ($baris[$kunci]->berat ?? 0), 2);
            $nilai[] = (int) ($baris[$kunci]->nilai ?? 0);
        }

        return ['labels' => $labels, 'berat' => $berat, 'nilai' => $nilai];
    }
}
