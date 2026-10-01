<?php

namespace App\Http\Controllers;

use App\Models\KoreksiTransaksi;
use Illuminate\Http\Request;

class KoreksiTransaksiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaksi_id' => 'required|integer',
            'tipe_transaksi' => 'required|in:anorganik,organik',
            'alasan' => 'required|string',
        ]);

        $koreksi = KoreksiTransaksi::create([
            'transaksi_id' => $validated['transaksi_id'],
            'tipe_transaksi' => $validated['tipe_transaksi'],
            'diajukan_oleh' => $request->user()->id,
            'status' => 'menunggu',
            'alasan' => $validated['alasan'],
        ]);

        return response()->json($koreksi, 201);
    }

    public function setujui(Request $request, KoreksiTransaksi $koreksi)
    {
        if ($koreksi->diajukan_oleh === $request->user()->id) {
            return response()->json([
                'message' => 'Koreksi tidak bisa disetujui oleh orang yang sama dengan pengaju.',
            ], 422);
        }

        $koreksi->status = 'disetujui';
        $koreksi->disetujui_oleh = $request->user()->id;
        $koreksi->save();

        return response()->json($koreksi);
    }
}