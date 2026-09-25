<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use Illuminate\Http\Request;

class PenarikanSaldoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nasabah_id' => 'required|exists:nasabah,id',
            'jumlah' => 'required|integer|min:1',
        ]);

        $nasabah = Nasabah::findOrFail($validated['nasabah_id']);

        if ($validated['jumlah'] > $nasabah->saldo) {
            return response()->json([
                'message' => 'Jumlah penarikan melebihi saldo yang tersedia.',
            ], 422);
        }

        $penarikan = PenarikanSaldo::create([
            'nasabah_id' => $validated['nasabah_id'],
            'jumlah' => $validated['jumlah'],
            'status' => 'pending',
        ]);

        return response()->json($penarikan, 201);
    }

    public function proses(Request $request, PenarikanSaldo $penarikan)
    {
        $penarikan->status = 'selesai';
        $penarikan->diproses_oleh = $request->user()->id;
        $penarikan->save();

        return response()->json($penarikan);
    }
}