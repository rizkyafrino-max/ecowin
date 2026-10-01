<?php

namespace App\Http\Controllers;

use App\Models\LaporanKendala;
use Illuminate\Http\Request;

class LaporanKendalaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|in:pencatatan,bug',
            'deskripsi' => 'required|string',
            'dilaporkan_oleh_nasabah_id' => 'nullable|exists:nasabah,id',
        ]);

        $laporan = LaporanKendala::create([
            'dilaporkan_oleh_nasabah_id' => $validated['dilaporkan_oleh_nasabah_id'] ?? null,
            'dilaporkan_oleh_user_id' => $validated['dilaporkan_oleh_nasabah_id'] ? null : $request->user()->id,
            'kategori' => $validated['kategori'],
            'deskripsi' => $validated['deskripsi'],
            'status' => 'menunggu',
        ]);

        return response()->json($laporan, 201);
    }

    public function index(Request $request)
    {
        $query = LaporanKendala::query();

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
        $validated = $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan_admin' => 'nullable|string',
        ]);

        $laporanKendala->status = $validated['status'];
        $laporanKendala->catatan_admin = $validated['catatan_admin'] ?? null;
        $laporanKendala->ditinjau_oleh = $request->user()->id;
        $laporanKendala->save();

        return response()->json($laporanKendala);
    }
}