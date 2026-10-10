<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiAnorganikRequest;
use App\Http\Resources\TransaksiAnorganikResource;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Services\TransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransaksiAnorganikController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date'], 'nasabah_id' => ['nullable', 'integer']]);

        $transaksi = TransaksiAnorganik::query()
            ->visibleTo($request->user())
            ->with(['nasabah:id,nama', 'hargaSampah.jenisSampah:id,nama_jenis'])
            ->when($request->user()->isStaff() && $request->filled('nasabah_id'), fn ($q) => $q->where('nasabah_id', $request->integer('nasabah_id')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('sampai')))
            ->latest('id')
            ->paginate(25);

        return TransaksiAnorganikResource::collection($transaksi);
    }

    public function show(TransaksiAnorganik $transaksi): TransaksiAnorganikResource
    {
        $this->authorize('view', $transaksi);

        return new TransaksiAnorganikResource($transaksi->load(['nasabah:id,nama', 'hargaSampah.jenisSampah']));
    }

    public function store(StoreTransaksiAnorganikRequest $request, TransaksiService $service): JsonResponse
    {
        $nasabah = Nasabah::query()->findOrFail($request->integer('nasabah_id'));
        $this->authorize('view', $nasabah);

        $transaksi = $service->catatAnorganik(
            $request->user(),
            $nasabah,
            JenisSampah::query()->findOrFail($request->integer('jenis_sampah_id')),
            (float) $request->input('berat_kg'),
            $request->input('kondisi'),
            $request->file('foto_dokumentasi'),
        );

        return (new TransaksiAnorganikResource($transaksi->load(['nasabah:id,nama', 'hargaSampah.jenisSampah'])))->response()->setStatusCode(201);
    }
}
