<?php

namespace App\Http\Controllers;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\Request;

class AktivitasBioporiController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof Nasabah || ($actor instanceof User && ($actor->isAdmin() || $actor->isPetugas())), 403);
        $query = AktivitasBiopori::with('nasabah')->latest();
        if ($actor instanceof Nasabah) {
            $query->where('nasabah_id', $actor->id);
        } elseif ($actor->isPetugas()) {
            $query->where('bank_sampah_id', $actor->bank_sampah_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof Nasabah, 403);
        $validated = $request->validate([
            'tanggal_pemasukan' => ['required', 'date'],
            'berat_kg' => ['nullable', 'numeric', 'gt:0'],
            'deskripsi' => ['nullable', 'string'],
            'foto_bukti' => ['required', 'image', 'max:5120'],
        ]);
        $validated['nasabah_id'] = $actor->id;
        $validated['bank_sampah_id'] = $actor->bank_sampah_id;
        $validated['foto_bukti_path'] = $request->file('foto_bukti')->store('bukti-biopori', 'public');
        unset($validated['foto_bukti']);

        return response()->json(AktivitasBiopori::create($validated), 201);
    }

    public function verifikasi(Request $request, AktivitasBiopori $aktivitas)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $aktivitas->bank_sampah_id)), 403);
        $data = $request->validate(['status' => ['required', 'in:disetujui,ditolak'], 'catatan_petugas' => ['nullable', 'string']]);
        abort_if($aktivitas->status !== 'menunggu', 422, 'Laporan ini sudah diverifikasi.');
        $aktivitas->update([...$data, 'diperiksa_oleh' => $actor->id, 'waktu_diperiksa' => now()]);

        return response()->json($aktivitas->fresh());
    }
}
