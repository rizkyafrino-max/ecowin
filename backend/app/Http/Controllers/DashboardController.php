<?php

namespace App\Http\Controllers;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function ringkasan(Request $request)
    {
        $actor = $request->user();
        if ($actor instanceof Nasabah) {
            return response()->json([
                'jumlah_nasabah' => 1,
                'total_transaksi_anorganik' => $actor->transaksiAnorganik()->count(),
                'total_berat_anorganik' => $actor->transaksiAnorganik()->sum('berat_kg'),
                'total_saldo' => $actor->saldo,
                'total_organik' => $actor->transaksiOrganik()->sum('berat_kg'),
                'estimasi_kompos' => $actor->transaksiOrganik()->sum('estimasi_kompos_kg'),
                'jumlah_aktivitas_biopori' => $actor->aktivitasBiopori()->count(),
                'biopori_menunggu' => $actor->aktivitasBiopori()->where('status', 'menunggu')->count(),
            ]);
        }
        abort_unless($actor instanceof User && ($actor->isAdmin() || $actor->isPetugas()), 403);
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
