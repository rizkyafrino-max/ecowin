<?php

namespace App\Http\Controllers;

use App\Models\HargaSampah;
use App\Models\JenisSampah;
use Illuminate\Http\Request;

class HargaSampahController extends Controller
{
    // GET /api/harga — daftar harga aktif, grouped by kategori → jenis → kondisi
    public function index()
    {
        $kategoriList = \App\Models\KategoriSampah::with(['jenisSampah.hargaSampah' => function ($query) {
            $query->orderByDesc('berlaku_mulai');
        }])->get();

        $hasil = $kategoriList->map(function ($kategori) {
            return [
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'jenis_sampah' => $kategori->jenisSampah->map(function ($jenis) {
                    // Ambil harga aktif per kondisi (baris terbaru untuk tiap kondisi unik)
                    $hargaPerKondisi = $jenis->hargaSampah
                        ->groupBy('kondisi')
                        ->map(fn ($grup) => $grup->first());

                    return [
                        'id' => $jenis->id,
                        'nama_jenis' => $jenis->nama_jenis,
                        'harga' => $hargaPerKondisi->values(),
                    ];
                }),
            ];
        });

        return response()->json($hasil);
    }

    // GET /api/harga/riwayat/{jenis_sampah_id}
    public function riwayat($jenisSampahId)
    {
        $riwayat = HargaSampah::where('jenis_sampah_id', $jenisSampahId)
            ->orderByDesc('berlaku_mulai')
            ->get();

        return response()->json($riwayat);
    }

    // POST /api/harga — tambah harga baru (selalu INSERT, bukan UPDATE)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_sampah_id' => 'required|exists:jenis_sampah,id',
            'kondisi' => 'required|string',
            'harga_per_kg' => 'required|integer|min:0',
            'berlaku_mulai' => 'nullable|date',
        ]);

        $harga = HargaSampah::create([
            'jenis_sampah_id' => $validated['jenis_sampah_id'],
            'kondisi' => $validated['kondisi'],
            'harga_per_kg' => $validated['harga_per_kg'],
            'berlaku_mulai' => $validated['berlaku_mulai'] ?? now(),
            'dibuat_oleh' => $request->user()->id,
        ]);

        return response()->json($harga, 201);
    }
}