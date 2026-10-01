<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NasabahController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $query = Nasabah::query()->when($actor->isPetugas(), fn ($q) => $q->where('bank_sampah_id', $actor->bank_sampah_id));
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$request->search}%")->orWhere('no_hp', 'like', "%{$request->search}%"));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:nasabah,username'],
            'no_hp' => ['required', 'string', 'unique:nasabah,no_hp'],
            'alamat_rt_rw' => ['required', 'string'],
            'nisn_atau_nik' => ['nullable', 'string'],
            'bank_sampah_id' => ['nullable', 'exists:bank_sampah,id'],
            'foto_ktp_kk' => ['nullable', 'image', 'max:2048'],
        ]);
        $bankId = $actor->isAdmin() ? ($validated['bank_sampah_id'] ?? null) : $actor->bank_sampah_id;
        abort_unless($bankId, 422, 'Bank sampah wajib dipilih.');
        $pin = (string) random_int(100000, 999999);
        $data = [
            'bank_sampah_id' => $bankId,
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'alamat_rt_rw' => $validated['alamat_rt_rw'],
            'nisn_atau_nik' => $validated['nisn_atau_nik'] ?? null,
            'pin' => $pin,
            'kartu_qr_token' => Str::random(48),
            'status_verifikasi' => 'verified',
            'dibuat_oleh' => $actor->id,
        ];
        if ($request->hasFile('foto_ktp_kk')) {
            $data['foto_ktp_kk_path'] = $request->file('foto_ktp_kk')->store('ktp-kk', 'public');
        }
        $nasabah = Nasabah::create($data);

        return response()->json(['nasabah' => $nasabah, 'pin_awal' => $pin], 201);
    }

    public function show(Request $request, Nasabah $nasabah)
    {
        $this->assertAccess($request, $nasabah);

        return response()->json(['nasabah' => $nasabah, 'saldo' => $nasabah->saldo, 'total_organik_kg' => $nasabah->transaksiOrganik()->sum('berat_kg')]);
    }

    public function scanByToken(Request $request, string $token)
    {
        abort_unless($request->user() instanceof User, 403);
        $nasabah = Nasabah::where('kartu_qr_token', $token)->firstOrFail();
        $this->assertAccess($request, $nasabah);

        return response()->json(['nasabah' => $nasabah, 'saldo' => $nasabah->saldo]);
    }

    public function verifikasi(Request $request, Nasabah $nasabah)
    {
        $this->assertAccess($request, $nasabah);
        abort_unless($request->user()->isAdmin() || $request->user()->isPetugas(), 403);
        $nasabah->update($request->validate(['status_verifikasi' => ['required', 'in:pending,verified']]));

        return response()->json($nasabah);
    }

    public function resetPin(Request $request, Nasabah $nasabah)
    {
        $this->assertAccess($request, $nasabah);
        $pin = (string) random_int(100000, 999999);
        $nasabah->update(['pin' => $pin]);

        return response()->json(['message' => 'PIN direset.', 'pin_baru' => $pin]);
    }

    private function assertAccess(Request $request, Nasabah $nasabah): void
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $nasabah->bank_sampah_id)) || $actor instanceof Nasabah && $actor->id === $nasabah->id, 403);
    }
}
