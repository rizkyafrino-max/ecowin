<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenarikanSaldoController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof Nasabah || ($actor instanceof User && ($actor->isAdmin() || $actor->isPetugas())), 403);
        $query = PenarikanSaldo::with('nasabah')->latest();
        if ($actor instanceof Nasabah) {
            $query->where('nasabah_id', $actor->id);
        } elseif ($actor->isPetugas()) {
            $query->where('bank_sampah_id', $actor->bank_sampah_id);
            if ($request->filled('nasabah_id')) {
                abort_unless(Nasabah::query()->whereKey($request->integer('nasabah_id'))->where('bank_sampah_id', $actor->bank_sampah_id)->exists(), 403);
                $query->where('nasabah_id', $request->integer('nasabah_id'));
            }
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof Nasabah || ($actor instanceof User && ($actor->isAdmin() || $actor->isPetugas())), 403);
        $validated = $request->validate(['nasabah_id' => ['nullable', 'exists:nasabah,id'], 'jumlah' => ['required', 'integer', 'min:1']]);
        abort_if($actor instanceof User && empty($validated['nasabah_id']), 422, 'Nasabah wajib dipilih petugas.');
        $nasabah = $actor instanceof Nasabah ? $actor : Nasabah::findOrFail($validated['nasabah_id']);
        abort_unless($actor instanceof Nasabah ? true : ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $nasabah->bank_sampah_id)), 403);
        $penarikan = DB::transaction(function () use ($validated, $nasabah) {
            $nasabah = Nasabah::query()->lockForUpdate()->findOrFail($nasabah->id);
            abort_if($validated['jumlah'] > $nasabah->saldo, 422, 'Jumlah penarikan melebihi saldo tersedia.');

            return PenarikanSaldo::create(['nasabah_id' => $nasabah->id, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'jumlah' => $validated['jumlah'], 'status' => 'pending']);
        });

        return response()->json($penarikan, 201);
    }

    public function proses(Request $request, PenarikanSaldo $penarikan)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $penarikan->bank_sampah_id)), 403);
        $validated = $request->validate(['status' => ['required', 'in:selesai,ditolak']]);
        DB::transaction(function () use ($penarikan, $actor, $validated) {
            $locked = PenarikanSaldo::query()->lockForUpdate()->findOrFail($penarikan->id);
            abort_unless($actor->isAdmin() || $actor->bank_sampah_id === $locked->bank_sampah_id, 403);
            abort_if($locked->status !== 'pending', 422, 'Penarikan sudah diproses.');
            $locked->update(['status' => $validated['status'], 'diproses_oleh' => $actor->id]);
        });

        $penarikan->refresh();

        return response()->json($penarikan);
    }
}
