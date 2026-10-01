<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Illuminate\Http\Request;

class PenarikanSaldoController extends Controller
{
    public function store(Request $request)
    {
        $actor = $request->user();
        $validated = $request->validate(['nasabah_id' => ['required', 'exists:nasabah,id'], 'jumlah' => ['required', 'integer', 'min:1']]);
        $nasabah = Nasabah::findOrFail($validated['nasabah_id']);
        abort_unless($actor instanceof Nasabah ? $actor->id === $nasabah->id : ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $nasabah->bank_sampah_id)), 403);
        abort_if($validated['jumlah'] > $nasabah->saldo, 422, 'Jumlah penarikan melebihi saldo.');

        return response()->json(PenarikanSaldo::create(['nasabah_id' => $nasabah->id, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'jumlah' => $validated['jumlah'], 'status' => 'pending']), 201);
    }

    public function proses(Request $request, PenarikanSaldo $penarikan)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $penarikan->bank_sampah_id)), 403);
        abort_if($penarikan->status !== 'pending', 422, 'Penarikan sudah diproses.');
        $penarikan->update(['status' => 'selesai', 'diproses_oleh' => $actor->id]);

        return response()->json($penarikan);
    }
}
