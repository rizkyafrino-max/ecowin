<?php

namespace App\Http\Controllers;

use App\Models\HargaSampah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    // POST /api/transaksi/anorganik
    public function storeAnorganik(Request $request)
    {
        $validated = $request->validate([
            'nasabah_id' => 'required|exists:nasabah,id',
            'harga_sampah_id' => 'required|exists:harga_sampah,id',
            'berat_kg' => 'required|numeric|min:0.01',
            'foto_dokumentasi' => 'nullable|image|max:2048',
        ]);

        $hargaSampah = HargaSampah::findOrFail($validated['harga_sampah_id']);
        $nilaiRupiah = $validated['berat_kg'] * $hargaSampah->harga_per_kg;

        $transaksi = new TransaksiAnorganik();
        $transaksi->nasabah_id = $validated['nasabah_id'];
        $transaksi->harga_sampah_id = $validated['harga_sampah_id'];
        $transaksi->berat_kg = $validated['berat_kg'];
        $transaksi->nilai_rupiah = $nilaiRupiah;
        $transaksi->dicatat_oleh = $request->user()->id;

        if ($request->hasFile('foto_dokumentasi')) {
            $transaksi->foto_dokumentasi_path = $request->file('foto_dokumentasi')->store('dokumentasi-anorganik', 'public');
        }

        $transaksi->save();

        return response()->json($transaksi, 201);
    }

    // POST /api/transaksi/organik
    public function storeOrganik(Request $request)
    {
        $validated = $request->validate([
            'nasabah_id' => 'required|exists:nasabah,id',
            'jenis_organik' => 'required|string',
            'berat_kg' => 'required|numeric|min:0.01',
            'checklist_bebas_plastik' => 'required|boolean',
            'checklist_bebas_logam' => 'required|boolean',
        ]);

        // Validasi wajib checklist true — aturan bisnis #3, dicek di backend, bukan cuma frontend
        if (! $validated['checklist_bebas_plastik'] || ! $validated['checklist_bebas_logam']) {
            return response()->json([
                'message' => 'Setoran organik wajib dipastikan bebas plastik dan bebas logam sebelum dicatat.',
            ], 422);
        }

        $estimasiKompos = $validated['berat_kg'] * 0.5;

        $transaksi = TransaksiOrganik::create([
            'nasabah_id' => $validated['nasabah_id'],
            'jenis_organik' => $validated['jenis_organik'],
            'berat_kg' => $validated['berat_kg'],
            'checklist_bebas_plastik' => $validated['checklist_bebas_plastik'],
            'checklist_bebas_logam' => $validated['checklist_bebas_logam'],
            'estimasi_kompos_kg' => $estimasiKompos,
            'dicatat_oleh' => $request->user()->id,
        ]);

        return response()->json($transaksi, 201);
    }

    // GET /api/transaksi?nasabah_id=&tipe=&dari=&sampai=
    public function index(Request $request)
    {
        $tipe = $request->get('tipe', 'semua');
        $hasil = [];

        if ($tipe === 'anorganik' || $tipe === 'semua') {
            $query = TransaksiAnorganik::query();

            if ($request->has('nasabah_id')) {
                $query->where('nasabah_id', $request->nasabah_id);
            }
            if ($request->has('dari')) {
                $query->whereDate('created_at', '>=', $request->dari);
            }
            if ($request->has('sampai')) {
                $query->whereDate('created_at', '<=', $request->sampai);
            }

            $hasil['anorganik'] = $query->latest()->get();
        }

        if ($tipe === 'organik' || $tipe === 'semua') {
            $query = TransaksiOrganik::query();

            if ($request->has('nasabah_id')) {
                $query->where('nasabah_id', $request->nasabah_id);
            }
            if ($request->has('dari')) {
                $query->whereDate('created_at', '>=', $request->dari);
            }
            if ($request->has('sampai')) {
                $query->whereDate('created_at', '<=', $request->sampai);
            }

            $hasil['organik'] = $query->latest()->get();
        }

        return response()->json($hasil);
    }
}