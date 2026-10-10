<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHargaSampahRequest;
use App\Http\Resources\HargaSampahResource;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\KategoriSampah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HargaSampahController extends Controller
{
    /**
     * Daftar harga AKTIF dikelompokkan per kategori & jenis (termasuk tingkatan berat).
     */
    public function index(): JsonResponse
    {
        $kategori = KategoriSampah::query()
            ->where('tipe', 'anorganik')
            ->with(['jenisSampah' => fn ($q) => $q->where('status', 'aktif')->orderBy('nama_jenis'),
                'jenisSampah.hargaSampah' => fn ($q) => $q->berlaku()->orderBy('minimal_berat')])
            ->orderBy('nama_kategori')
            ->get();

        return response()->json(['data' => $kategori->map(fn (KategoriSampah $k) => [
            'id' => $k->id,
            'nama_kategori' => $k->nama_kategori,
            'jenis_sampah' => $k->jenisSampah->map(fn (JenisSampah $j) => [
                'id' => $j->id,
                'nama_jenis' => $j->nama_jenis,
                'satuan' => $j->satuan,
                'harga' => HargaSampahResource::collection($j->hargaSampah),
            ])->values(),
        ])->values()]);
    }

    /**
     * Harga aktif untuk satu jenis sampah.
     */
    public function show(JenisSampah $jenisSampah): AnonymousResourceCollection
    {
        return HargaSampahResource::collection(
            $jenisSampah->hargaSampah()->berlaku()->orderBy('minimal_berat')->get()
        );
    }

    public function riwayat(JenisSampah $jenisSampah): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HargaSampah::class);

        return HargaSampahResource::collection($jenisSampah->hargaSampah()->latest('berlaku_mulai')->paginate(50));
    }

    /**
     * Harga baru selalu INSERT (riwayat tetap utuh). Hanya Admin.
     */
    public function store(StoreHargaSampahRequest $request): JsonResponse
    {
        $this->authorize('create', HargaSampah::class);

        $harga = new HargaSampah($request->safe()->except('berlaku_mulai'));
        $harga->forceFill([
            'berlaku_mulai' => $request->date('berlaku_mulai') ?? now(),
            'dibuat_oleh' => $request->user()->id,
        ])->save();

        return (new HargaSampahResource($harga))->response()->setStatusCode(201);
    }
}
