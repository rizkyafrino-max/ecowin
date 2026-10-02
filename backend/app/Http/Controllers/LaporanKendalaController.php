<?php

namespace App\Http\Controllers;

use App\Models\LaporanKendala;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanKendalaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|in:pencatatan,bug',
            'deskripsi' => 'required|string',
            'dilaporkan_oleh_nasabah_id' => 'nullable|exists:nasabah,id',
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof Nasabah || $actor instanceof User, 403);
        if ($actor instanceof Nasabah) {
            $validated['dilaporkan_oleh_nasabah_id'] = $actor->id;
        }
        $nasabahId = $actor instanceof Nasabah ? $actor->id : ($validated['dilaporkan_oleh_nasabah_id'] ?? null);
        $nasabah = $nasabahId ? Nasabah::findOrFail($nasabahId) : null;
        abort_unless(($actor instanceof Nasabah && $nasabah?->id === $actor->id) || ($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && (! $nasabah || $nasabah->bank_sampah_id === $actor->bank_sampah_id)))), 403);

        $laporan = LaporanKendala::create([
            'bank_sampah_id' => $nasabah?->bank_sampah_id ?? ($actor instanceof User ? $actor->bank_sampah_id : null),
            'dilaporkan_oleh_nasabah_id' => $actor instanceof Nasabah ? $actor->id : ($validated['dilaporkan_oleh_nasabah_id'] ?? null),
            'dilaporkan_oleh_user_id' => $actor instanceof User ? $actor->id : null,
            'kategori' => $validated['kategori'],
            'deskripsi' => $validated['deskripsi'],
            'status' => 'menunggu',
        ]);

        return response()->json($laporan, 201);
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $query = LaporanKendala::query();
        if ($actor->isPetugas()) {
            $query->where('bank_sampah_id', $actor->bank_sampah_id);
        }

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->get());
    }

    public function update(Request $request, LaporanKendala $laporanKendala)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $laporanKendala->bank_sampah_id)), 403);
        $validated = $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan_admin' => 'nullable|string',
        ]);

        DB::transaction(function () use ($laporanKendala, $validated, $actor): void {
            $laporanKendala = LaporanKendala::query()->lockForUpdate()->findOrFail($laporanKendala->id);
            abort_unless($actor->isAdmin() || $actor->bank_sampah_id === $laporanKendala->bank_sampah_id, 403);
            abort_if($laporanKendala->status !== 'menunggu', 422, 'Laporan ini sudah ditinjau.');
            $laporanKendala->update([
                'status' => $validated['status'],
                'catatan_admin' => $validated['catatan_admin'] ?? null,
                'ditinjau_oleh' => $actor->id,
            ]);
        });

        $laporanKendala->refresh();

        return response()->json($laporanKendala);
    }
}
