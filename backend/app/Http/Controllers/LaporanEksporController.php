<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MenyaringLaporan;
use App\Models\PenarikanSaldo;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Services\AuditLogger;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV laporan (siap dibuka di Excel): pemisah titik-koma, angka berkoma desimal, UTF-8 dengan BOM.
 * Hak akses sama dengan cetak laporan. Sel teks yang berawalan = + - @ dinetralkan agar tidak dieksekusi sebagai rumus.
 */
class LaporanEksporController extends Controller
{
    use MenyaringLaporan;

    /** Batas baris per ekspor agar memori dan waktu tetap terkendali. */
    private const BATAS = 50000;

    public function csv(Request $request, string $jenis, LaporanService $laporan, AuditLogger $audit): StreamedResponse
    {
        abort_unless(in_array($jenis, ['anorganik', 'organik', 'penarikan', 'rekap'], true), 404);
        $user = $request->user();
        ['dari' => $dari, 'sampai' => $sampai, 'bankId' => $bankId, 'bank' => $bank] = $this->filterLaporan($request);

        $audit->log('ekspor_laporan', null, null, ['jenis' => $jenis, 'bank_sampah_id' => $bankId, 'dari' => $dari?->toDateString(), 'sampai' => $sampai?->toDateString()], $user);

        $nama = 'laporan-'.$jenis.'-'.Str::slug($bank?->nama_bank_sampah ?? 'semua-bank').'-'.($dari?->toDateString() ?? 'awal').'_sd_'.($sampai?->toDateString() ?? 'kini').'.csv';

        return response()->streamDownload(function () use ($jenis, $user, $laporan, $dari, $sampai, $bankId): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel membaca UTF-8 dengan benar

            $tulis = fn (array $baris) => fputcsv($out, array_map([$this, 'aman'], $baris), ';', '"', '\\');
            $kg = fn ($v) => number_format((float) $v, 2, ',', '');
            $rp = fn ($v) => number_format((float) $v, 0, ',', '');

            match ($jenis) {
                'anorganik' => $this->anorganik($laporan->kueri($user, 'anorganik', $dari, $sampai, $bankId), $tulis, $kg, $rp),
                'organik' => $this->organik($laporan->kueri($user, 'organik', $dari, $sampai, $bankId), $tulis, $kg),
                'penarikan' => $this->penarikan($laporan->kueri($user, 'penarikan', $dari, $sampai, $bankId), $tulis, $rp),
                'rekap' => $this->rekap($laporan, $user, $dari, $sampai, $bankId, $tulis, $kg, $rp),
            };
            fclose($out);
        }, $nama, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Mengambil baris terbaru per 500 (relasi ikut dimuat) sampai BATAS baris. */
    private function perHalaman(\Illuminate\Database\Eloquent\Builder $kueri, callable $tiapHalaman): void
    {
        $terkirim = 0;
        for ($halaman = 1; $terkirim < self::BATAS; $halaman++) {
            $baris = (clone $kueri)->latest('id')->forPage($halaman, 500)->get();
            if ($baris->isEmpty()) {
                return;
            }
            $tiapHalaman($baris->take(self::BATAS - $terkirim));
            $terkirim += $baris->count();
            if ($baris->count() < 500) {
                return;
            }
        }
    }

    /** Menetralkan injeksi rumus spreadsheet pada sel teks. */
    public function aman(mixed $nilai): mixed
    {
        if (! is_string($nilai) || $nilai === '') {
            return $nilai;
        }

        return in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! preg_match('/^-?\d+([.,]\d+)?$/', $nilai) ? "'".$nilai : $nilai;
    }

    private function anorganik($kueri, callable $tulis, callable $kg, callable $rp): void
    {
        $tulis(['Tanggal', 'Nomor nasabah', 'Nasabah', 'Bank Sampah', 'Jenis sampah', 'Berat (kg)', 'Harga per kg (Rp)', 'Total (Rp)']);
        $this->perHalaman($kueri, function ($baris) use ($tulis, $kg, $rp): void {
            /** @var TransaksiAnorganik $t */
            foreach ($baris as $t) {
                $tulis([$t->created_at?->format('Y-m-d H:i'), $t->nasabah?->nomor_nasabah, $t->nasabah?->nama, $t->bankSampah?->nama_bank_sampah, $t->hargaSampah?->jenisSampah?->nama_jenis, $kg($t->berat_kg), $rp($t->harga_per_kg), $rp($t->nilai_rupiah)]);
            }
        });
    }

    private function organik($kueri, callable $tulis, callable $kg): void
    {
        $tulis(['Tanggal', 'Nomor nasabah', 'Nasabah', 'Bank Sampah', 'Jenis organik', 'Metode', 'Berat (kg)', 'Estimasi kompos (kg)']);
        $this->perHalaman($kueri, function ($baris) use ($tulis, $kg): void {
            /** @var TransaksiOrganik $t */
            foreach ($baris as $t) {
                $tulis([($t->tanggal ?? $t->created_at)?->format('Y-m-d'), $t->nasabah?->nomor_nasabah, $t->nasabah?->nama, $t->bankSampah?->nama_bank_sampah, $t->jenis_organik, $t->metode_pengolahan, $kg($t->berat_kg), $kg($t->estimasi_kompos_kg)]);
            }
        });
    }

    private function penarikan($kueri, callable $tulis, callable $rp): void
    {
        $tulis(['Diajukan', 'Nomor nasabah', 'Nasabah', 'Bank Sampah', 'Status', 'Jumlah (Rp)']);
        $this->perHalaman($kueri, function ($baris) use ($tulis, $rp): void {
            /** @var PenarikanSaldo $t */
            foreach ($baris as $t) {
                $tulis([$t->created_at?->format('Y-m-d H:i'), $t->nasabah?->nomor_nasabah, $t->nasabah?->nama, $t->bankSampah?->nama_bank_sampah, $t->status, $rp($t->jumlah)]);
            }
        });
    }

    private function rekap(LaporanService $laporan, $user, $dari, $sampai, ?int $bankId, callable $tulis, callable $kg, callable $rp): void
    {
        $tulis(['Bank Sampah', 'RT/RW', 'Nasabah', 'Anorganik (kg)', 'Nilai anorganik (Rp)', 'Organik (kg)', 'Penarikan (Rp)', 'Saldo nasabah (Rp)']);
        // Rekap per bank hanya untuk Admin; Petugas/pilihan satu bank menghasilkan satu baris milik bank itu.
        $baris = $user->isAdmin() && $bankId === null
            ? $laporan->rekapPerBank($user, $dari, $sampai)
            : [$this->barisTunggal($laporan, $user, $dari, $sampai, $bankId)];
        foreach ($baris as $b) {
            $tulis([$b['bank'], $b['rtrw'], $b['nasabah'], $kg($b['anorganik_kg']), $rp($b['anorganik_nilai']), $kg($b['organik_kg']), $rp($b['penarikan']), $rp($b['saldo'])]);
        }
    }

    /** @return array<string, mixed> */
    private function barisTunggal(LaporanService $laporan, $user, $dari, $sampai, ?int $bankId): array
    {
        $id = $user->isAdmin() ? $bankId : (int) $user->bank_sampah_id;
        $bank = \App\Models\BankSampah::query()->find($id);
        $r = $laporan->ringkasan($user, $dari, $sampai, $id);

        return [
            'bank' => $bank?->nama_bank_sampah ?? '-',
            'rtrw' => $bank ? 'RT '.$bank->rt.' / RW '.$bank->rw : '-',
            'nasabah' => $r['umum']['total_nasabah'],
            'anorganik_kg' => $r['anorganik']['total_berat_kg'],
            'anorganik_nilai' => $r['anorganik']['total_nilai'],
            'organik_kg' => $r['organik']['total_berat_kg'],
            'penarikan' => $r['keuangan']['total_penarikan'],
            'saldo' => $r['keuangan']['total_saldo_nasabah'],
        ];
    }
}
