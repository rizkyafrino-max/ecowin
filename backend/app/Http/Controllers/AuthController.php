<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/auth/login — login admin/petugas
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function loginNasabah(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $nasabah = Nasabah::where('no_hp', $validated['no_hp'])->first();

        if (! $nasabah || ! Hash::check($validated['pin'], $nasabah->pin)) {
            return response()->json(['message' => 'Nomor HP atau PIN salah.'], 422);
        }

        if ($nasabah->status_verifikasi !== 'verified') {
            return response()->json(['message' => 'Akun nasabah belum diverifikasi.'], 403);
        }

        return response()->json([
            'nasabah' => $nasabah,
            'token' => $nasabah->createToken('nasabah-android')->plainTextToken,
        ]);
    }

    public function changeNasabahPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin_lama' => ['required', 'string'],
            'pin_baru' => ['required', 'string', 'min:4', 'max:12'],
        ]);
        $nasabah = $request->user();

        abort_unless($nasabah instanceof Nasabah, 403);
        if (! Hash::check($validated['pin_lama'], $nasabah->pin)) {
            return response()->json(['message' => 'PIN lama salah.'], 422);
        }
        $nasabah->update(['pin' => $validated['pin_baru']]);

        return response()->json(['message' => 'PIN berhasil diubah.']);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
