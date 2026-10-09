<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AktivitasBioporiResource;
use App\Http\Resources\TransaksiAnorganikResource;
use App\Models\AktivitasBiopori;
use App\Models\TransaksiAnorganik;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private LaporanService $laporan) {}

    /**
     * Ringkasan dashboard sesuai role (nasabah: data sendiri saja).
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'nama' => $user->nasabah?->nama ?? $user->nama,
            'saldo' => $user->nasabah ? (float) $user->nasabah->saldo : null,
            ...$this->laporan->ringkasan($user),
            'transaksi_terakhir' => TransaksiAnorganikResource::collection(
                TransaksiAnorganik::query()->visibleTo($user)->with('hargaSampah.jenisSampah')->latest('id')->limit(5)->get()
            ),
            'biopori_terakhir' => AktivitasBioporiResource::collection(
                AktivitasBiopori::query()->visibleTo($user)->with('titikBiopori')->latest('id')->limit(5)->get()
            ),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $request->validate(['bulan' => ['nullable', 'integer', 'min:1', 'max:24']]);

        return response()->json($this->laporan->grafikBulanan($request->user(), $request->integer('bulan', 6)));
    }
}
