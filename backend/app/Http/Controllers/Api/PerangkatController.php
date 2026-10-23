<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerangkatPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendaftaran token FCM perangkat nasabah. Token hanya dipakai untuk membangunkan aplikasi; isi notifikasi tidak
 * pernah dikirim lewat FCM (aplikasi mengambilnya dari API yang terautentikasi).
 */
class PerangkatController extends Controller
{
    /** Batas perangkat per akun; yang paling lama tidak aktif dihapus saat melebihi batas. */
    private const MAKS_PERANGKAT = 5;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096', 'regex:/^[\x21-\x7E]+$/'], // ASCII tercetak, tanpa spasi
            'platform' => ['sometimes', 'in:android'],
        ]);

        $user = $request->user();
        $hash = PerangkatPush::hash($data['token']);

        $p = PerangkatPush::query()->where('token_hash', $hash)->first() ?? new PerangkatPush;
        // Perangkat yang sama dipakai akun lain: kepemilikan berpindah (akun sebelumnya berhenti menerima).
        $p->forceFill([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'token' => $data['token'],
            'platform' => $data['platform'] ?? 'android',
            'terakhir_aktif_at' => now(),
        ])->save();

        $lama = PerangkatPush::query()->where('user_id', $user->id)->orderByDesc('terakhir_aktif_at')->orderByDesc('id')->pluck('id')->slice(self::MAKS_PERANGKAT);
        if ($lama->isNotEmpty()) {
            PerangkatPush::query()->whereIn('id', $lama)->delete();
        }

        return response()->json(['message' => 'Perangkat terdaftar.'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);

        // Hanya milik pengguna ini yang bisa dihapus; token milik orang lain diperlakukan seperti tidak ada.
        PerangkatPush::query()->where('user_id', $request->user()->id)->where('token_hash', PerangkatPush::hash($data['token']))->delete();

        return response()->json(['message' => 'Perangkat dilepas.']);
    }
}
