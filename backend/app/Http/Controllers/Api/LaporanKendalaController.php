<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaporanKendalaRequest;
use App\Models\LaporanKendala;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanKendalaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', 'in:menunggu,disetujui,ditolak'], 'kategori' => ['nullable', 'in:pencatatan,bug']]);

        return response()->json(
            LaporanKendala::query()
                ->visibleTo($request->user())
                ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->string('kategori')))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate(25)
        );
    }

    /**
     * Pelapor selalu diambil dari akun login, bukan dari input.
     */
    public function store(StoreLaporanKendalaRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $laporan = new LaporanKendala($request->validated());
        $laporan->forceFill([
            'bank_sampah_id' => $user->bank_sampah_id ?? $user->nasabah?->bank_sampah_id,
            'dilaporkan_oleh_nasabah_id' => $user->nasabah?->id,
            'dilaporkan_oleh_user_id' => $user->id,
            'status' => 'menunggu',
        ])->save();

        $audit->log('buat_laporan_kendala', $laporan, null, $laporan->attributesToArray(), $user);

        return response()->json($laporan, 201);
    }

    public function update(Request $request, LaporanKendala $laporanKendala, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $laporanKendala);

        $data = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $laporanKendala->attributesToArray();
        $laporanKendala->forceFill([...$data, 'ditinjau_oleh' => $request->user()->id])->save();
        $audit->log('ubah_laporan_kendala', $laporanKendala, $before, $laporanKendala->attributesToArray());

        return response()->json($laporanKendala);
    }
}
