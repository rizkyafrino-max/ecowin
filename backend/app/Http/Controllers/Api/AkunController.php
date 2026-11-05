<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PenghapusanAkunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Nasabah menghapus akunnya sendiri (wajib untuk Google Play). Data pribadi dihapus permanen; riwayat
 * transaksi tetap tersimpan secara anonim. Syarat: saldo Rp 0 dan tidak ada permintaan yang menunggu.
 */
class AkunController extends Controller
{
    public function hapus(Request $request, PenghapusanAkunService $service): JsonResponse
    {
        $request->validate(['konfirmasi' => ['required', 'string', 'in:HAPUS']], [
            'konfirmasi.required' => 'Ketik HAPUS untuk mengonfirmasi.',
            'konfirmasi.in' => 'Ketik HAPUS (huruf besar) untuk mengonfirmasi.',
        ]);

        $service->hapusAkunSendiri($request->user());

        return response()->json(['message' => 'Akunmu sudah dihapus.']);
    }
}
