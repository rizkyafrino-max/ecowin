<?php

namespace App\Http\Controllers;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function ringkasan(Request $request)
    {
        $actor = $request->user();
        if ($actor instanceof Nasabah) {
            $anorganik = DB::table('transaksi_anorganik')
                ->where('nasabah_id', $actor->id)
                ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(berat_kg), 0) as total_berat, COALESCE(SUM(nilai_rupiah), 0) as total_nilai')
                ->first();
            $organik = DB::table('transaksi_organik')
                ->where('nasabah_id', $actor->id)
                ->selectRaw('COALESCE(SUM(berat_kg), 0) as total_berat, COALESCE(SUM(estimasi_kompos_kg), 0) as estimasi_kompos')
                ->first();
            $biopori = DB::table('aktivitas_biopori')
                ->where('nasabah_id', $actor->id)
                ->selectRaw('COUNT(*) as total_aktivitas, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as menunggu', ['menunggu'])
                ->first();
            $penarikan = DB::table('penarikan_saldo')
                ->where('nasabah_id', $actor->id)
                ->where('status', 'selesai')
                ->sum('jumlah');

            return response()->json([
                'jumlah_nasabah' => 1,
                'total_transaksi_anorganik' => (int) $anorganik->total_transaksi,
                'total_berat_anorganik' => (float) $anorganik->total_berat,
                'total_saldo' => (int) $anorganik->total_nilai - (int) $penarikan,
                'total_organik' => (float) $organik->total_berat,
                'estimasi_kompos' => (float) $organik->estimasi_kompos,
                'jumlah_aktivitas_biopori' => (int) $biopori->total_aktivitas,
                'biopori_menunggu' => (int) $biopori->menunggu,
            ]);
        }
        $bankId = $actor instanceof User && $actor->isPetugas() ? $actor->bank_sampah_id : null;
        $scope = fn ($q) => $bankId ? $q->where('bank_sampah_id', $bankId) : $q;
        $nasabah = Nasabah::when($bankId, fn ($q) => $q->where('bank_sampah_id', $bankId));

        return response()->json([
            'jumlah_nasabah' => $nasabah->count(),
            'total_transaksi_anorganik' => $scope(TransaksiAnorganik::query())->count(),
            'total_berat_anorganik' => $scope(TransaksiAnorganik::query())->sum('berat_kg'),
            'total_saldo' => $scope(TransaksiAnorganik::query())->sum('nilai_rupiah'),
            'total_organik' => $scope(TransaksiOrganik::query())->sum('berat_kg'),
            'estimasi_kompos' => $scope(TransaksiOrganik::query())->sum('estimasi_kompos_kg'),
            'jumlah_aktivitas_biopori' => $scope(AktivitasBiopori::query())->count(),
            'biopori_menunggu' => $scope(AktivitasBiopori::query())->where('status', 'menunggu')->count(),
        ]);
    }
}
