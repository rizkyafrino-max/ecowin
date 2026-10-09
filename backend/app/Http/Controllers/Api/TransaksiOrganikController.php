<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiOrganikRequest;
use App\Http\Resources\TransaksiOrganikResource;
use App\Models\Nasabah;
use App\Models\TransaksiOrganik;
use App\Services\TransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransaksiOrganikController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date']]);

        return TransaksiOrganikResource::collection(
            TransaksiOrganik::query()
                ->visibleTo($request->user())
                ->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('dari')))
                ->when($request->filled('sampai'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('sampai')))
                ->latest('id')
                ->paginate(25)
        );
    }

    public function show(TransaksiOrganik $transaksi): TransaksiOrganikResource
    {
        $this->authorize('view', $transaksi);

        return new TransaksiOrganikResource($transaksi);
    }

    public function store(StoreTransaksiOrganikRequest $request, TransaksiService $service): JsonResponse
    {
        $nasabah = Nasabah::query()->findOrFail($request->integer('nasabah_id'));
        $this->authorize('view', $nasabah);

        $transaksi = $service->catatOrganik($request->user(), $nasabah, $request->safe()->only(['jenis_organik', 'berat_kg', 'metode_pengolahan', 'lokasi', 'tanggal']));

        return (new TransaksiOrganikResource($transaksi))->response()->setStatusCode(201);
    }
}
