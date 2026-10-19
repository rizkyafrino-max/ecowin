<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MenyaringLaporan;
use App\Services\AuditLogger;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Laporan siap cetak / simpan PDF (lewat dialog cetak browser).
 * Admin: semua Bank Sampah atau satu Bank Sampah pilihan. Petugas: selalu hanya Bank Sampah yang dikelolanya,
 * apa pun nilai parameter yang dikirim. Semua angka diambil lewat scope visibleTo() di LaporanService.
 */
class LaporanCetakController extends Controller
{
    use MenyaringLaporan;

    public function show(Request $request, LaporanService $laporan, AuditLogger $audit): Response
    {
        $user = $request->user();
        ['dari' => $dari, 'sampai' => $sampai, 'bankId' => $bankId, 'bank' => $bank] = $this->filterLaporan($request);

        $audit->log('cetak_laporan', null, null, [
            'bank_sampah_id' => $bankId,
            'dari' => $dari?->toDateString(),
            'sampai' => $sampai?->toDateString(),
        ], $user);

        $html = view('laporan-cetak', [
            'r' => $laporan->ringkasan($user, $dari, $sampai, $bankId),
            'detail' => $laporan->detail($user, $dari, $sampai, $bankId),
            'rekap' => $bankId === null ? $laporan->rekapPerBank($user, $dari, $sampai) : [],
            'bank' => $bank,
            'dari' => $dari,
            'sampai' => $sampai,
            'user' => $user,
            'cetakOtomatis' => $request->boolean('print'),
        ])->render();

        // Data keuangan: jangan disimpan di cache bersama atau riwayat perantara.
        return response($html)->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
