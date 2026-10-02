<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\Request;

class NasabahController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($actor->isAdmin() || $actor->isPetugas(), 403);
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
        abort_unless($actor->isAdmin() || $actor->isPetugas(), 403);
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:nasabah,username'],
            'no_hp' => ['required', 'string'],
            'alamat_rt_rw' => ['required', 'string'],
            'nisn_atau_nik' => ['nullable', 'string'],
            'bank_sampah_id' => ['nullable', 'exists:bank_sampah,id'],
            'foto_ktp_kk' => ['nullable', 'image', 'max:2048'],
        ]);
        $bankId = $actor->isAdmin() ? ($validated['bank_sampah_id'] ?? null) : $actor->bank_sampah_id;
        abort_unless($bankId, 422, 'Bank sampah wajib dipilih.');
        if ($actor->isAdmin() && empty($validated['username'])) {
            do {
                $username = 'nasabah'.Str::lower(Str::random(8));
            } while (Nasabah::where('username', $username)->exists());
        } else {
            $username = $validated['username'];
        }

        $pin = (string) random_int(100000, 999999);
        $data = [
            'bank_sampah_id' => $bankId,
            'username' => $username,
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

        return response()->json(['nasabah' => $nasabah, 'username' => $username, 'pin_awal' => $pin], 201);
    }

    public function show(Request $request, Nasabah $nasabah)
    {
        $this->assertAccess($request, $nasabah);

        return response()->json(['nasabah' => $nasabah, 'saldo' => $nasabah->saldo, 'total_organik_kg' => $nasabah->transaksiOrganik()->sum('berat_kg')]);
    }

    public function me(Request $request)
    {
        $nasabah = $request->user();
        abort_unless($nasabah instanceof Nasabah, 403);

        return response()->json(['nasabah' => $nasabah, 'saldo' => $nasabah->saldo]);
    }

    public function kartu(Request $request)
    {
        $nasabah = $request->user();
        abort_unless($nasabah instanceof Nasabah, 403);

        return response()->json(['qr_token' => $nasabah->kartu_qr_token, 'username' => $nasabah->username, 'nama' => $nasabah->nama]);
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
        abort_unless($request->user() instanceof User, 403);
        $pin = (string) config('ecowin.default_nasabah_pin', '123456');
        $nasabah->update(['pin' => $pin]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'bank_sampah_id' => $nasabah->bank_sampah_id,
            'aksi' => 'reset_pin_nasabah',
            'detail' => json_encode(['nasabah_id' => $nasabah->id]),
        ]);

        return response()->json(['message' => 'PIN direset.', 'pin_baru' => $pin]);
    }

    private function assertAccess(Request $request, Nasabah $nasabah): void
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $nasabah->bank_sampah_id)) || $actor instanceof Nasabah && $actor->id === $nasabah->id, 403);
    }
}
