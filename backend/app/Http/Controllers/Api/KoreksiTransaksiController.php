<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\StoreKoreksiRequest;
use App\Models\KoreksiTransaksi;
use App\Services\KoreksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KoreksiTransaksiController extends Controller
{
    public function __construct(private KoreksiService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KoreksiTransaksi::class);

        return response()->json(KoreksiTransaksi::query()->visibleTo($request->user())->latest('id')->paginate(25));
    }

    public function store(StoreKoreksiRequest $request): JsonResponse
    {
        $koreksi = $this->service->ajukan(
            $request->user(),
            $request->string('tipe_transaksi'),
            $request->integer('transaksi_id'),
            (float) $request->input('berat_kg'),
            $request->string('alasan'),
        );

        return response()->json($koreksi, 201);
    }

    public function approve(DecisionRequest $request, KoreksiTransaksi $koreksi): JsonResponse
    {
        $this->authorize('decide', $koreksi);

        return response()->json($this->service->setujui($request->user(), $koreksi, $request->input('catatan')));
    }

    public function reject(DecisionRequest $request, KoreksiTransaksi $koreksi): JsonResponse
    {
        $this->authorize('decide', $koreksi);

        return response()->json($this->service->tolak($request->user(), $koreksi, (string) $request->input('catatan')));
    }
}
