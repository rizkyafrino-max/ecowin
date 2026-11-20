<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TransaksiAnorganik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Beranda petugas di aplikasi Android: angka pekerjaan yang menunggu dan setoran hari ini, dibatasi ke Bank Sampah petugas
 * (Admin melihat semuanya). Hanya jumlah, tanpa data pribadi.
 */
class PetugasController extends Controller
{
    public function ringkasan(Request $request): JsonResponse
    {
        $user = $request->user();
        $bank = $user->isAdmin() ? null : $user->bankSampah;

        $hariIni = TransaksiAnorganik::query()->visibleTo($user)->whereDate('created_at', today());

        return response()->json([
            'bank_sampah' => $bank ? ['id' => $bank->id, 'nama' => $bank->nama_bank_sampah, 'rt' => $bank->rt, 'rw' => $bank->rw] : null,
            'menunggu_verifikasi' => Nasabah::query()->visibleTo($user)->where('status', 'aktif')->where('status_verifikasi', 'pending')->count(),
            'penarikan_menunggu' => PenarikanSaldo::query()->visibleTo($user)->where('status', PenarikanSaldo::STATUS_PENDING)->count(),
            'setoran_hari_ini' => [
                'jumlah' => (clone $hariIni)->count(),
                'nilai_rupiah' => (int) (clone $hariIni)->sum('nilai_rupiah'),
            ],
        ]);
    }
}
