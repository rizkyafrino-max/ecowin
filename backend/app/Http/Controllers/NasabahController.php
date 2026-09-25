<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NasabahController extends Controller
{
    // GET /api/nasabah
    public function index(Request $request)
    {
        $query = Nasabah::query();

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%')
                ->orWhere('no_hp', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->get());
    }

    // POST /api/nasabah
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|unique:nasabah,no_hp',
            'alamat_rt_rw' => 'required|string',
            'nisn_atau_nik' => 'nullable|string',
            'foto_ktp_kk' => 'nullable|image|max:2048',
        ]);

        $nasabah = new Nasabah();
        $nasabah->nama = $validated['nama'];
        $nasabah->no_hp = $validated['no_hp'];
        $nasabah->alamat_rt_rw = $validated['alamat_rt_rw'];
        $nasabah->nisn_atau_nik = $validated['nisn_atau_nik'] ?? null;
        $nasabah->kartu_qr_token = Str::random(32);
        $nasabah->status_verifikasi = 'pending';
        $nasabah->dibuat_oleh = $request->user()->id;

        if ($request->hasFile('foto_ktp_kk')) {
            $nasabah->foto_ktp_kk_path = $request->file('foto_ktp_kk')->store('ktp-kk', 'public');
        }

        $nasabah->save();

        return response()->json($nasabah, 201);
    }

    // GET /api/nasabah/{id}
    public function show(Nasabah $nasabah)
    {
        return response()->json([
            'nasabah' => $nasabah,
            'saldo' => $nasabah->saldo,
            'total_organik_kg' => $nasabah->transaksiOrganik()->sum('berat_kg'),
        ]);
    }

    // GET /api/nasabah/scan/{token}
    public function scanByToken($token)
    {
        $nasabah = Nasabah::where('kartu_qr_token', $token)->firstOrFail();

        return response()->json([
            'nasabah' => $nasabah,
            'saldo' => $nasabah->saldo,
        ]);
    }

    // PATCH /api/nasabah/{id}/verifikasi
    public function verifikasi(Request $request, Nasabah $nasabah)
    {
        $request->validate([
            'status_verifikasi' => 'required|in:pending,verified',
        ]);

        $nasabah->status_verifikasi = $request->status_verifikasi;
        $nasabah->save();

        return response()->json($nasabah);
    }
}