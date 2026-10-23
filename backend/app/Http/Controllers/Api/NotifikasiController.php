<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi dalam aplikasi untuk nasabah. Selalu dibatasi ke pengguna yang login; tidak ada ID yang bisa
 * dipakai untuk membaca notifikasi orang lain.
 */
class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $uid = $request->user()->id;
        $daftar = Notifikasi::query()->where('user_id', $uid)->latest('id')->limit(40)->get()
            ->map(fn (Notifikasi $n): array => [
                'id' => $n->id,
                'tipe' => $n->tipe,
                'judul' => $n->judul,
                'isi' => $n->isi,
                'aksi' => $n->aksi,
                'dibaca' => $n->dibaca_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $daftar,
            'belum_dibaca' => Notifikasi::query()->where('user_id', $uid)->whereNull('dibaca_at')->count(),
        ]);
    }

    public function jumlah(Request $request): JsonResponse
    {
        return response()->json([
            'belum_dibaca' => Notifikasi::query()->where('user_id', $request->user()->id)->whereNull('dibaca_at')->count(),
        ]);
    }

    public function bacaSemua(Request $request): JsonResponse
    {
        Notifikasi::query()->where('user_id', $request->user()->id)->whereNull('dibaca_at')->update(['dibaca_at' => now()]);

        return response()->json(['belum_dibaca' => 0]);
    }

    /** Hapus satu notifikasi milik sendiri. */
    public function hapus(Request $request, int $id): JsonResponse
    {
        $uid = $request->user()->id;
        $n = Notifikasi::query()->where('user_id', $uid)->whereKey($id)->first();
        abort_if($n === null, 404);
        $n->delete();

        return response()->json(['belum_dibaca' => Notifikasi::query()->where('user_id', $uid)->whereNull('dibaca_at')->count()]);
    }

    /** Hapus semua notifikasi milik sendiri; ?hanya_dibaca=1 hanya yang sudah dibaca. */
    public function hapusSemua(Request $request): JsonResponse
    {
        $uid = $request->user()->id;
        $q = Notifikasi::query()->where('user_id', $uid);
        if ($request->boolean('hanya_dibaca')) {
            $q->whereNotNull('dibaca_at');
        }
        $dihapus = $q->delete();

        return response()->json(['dihapus' => $dihapus, 'belum_dibaca' => Notifikasi::query()->where('user_id', $uid)->whereNull('dibaca_at')->count()]);
    }

    public function baca(Request $request, int $id): JsonResponse
    {
        $n = Notifikasi::query()->where('user_id', $request->user()->id)->whereKey($id)->first();
        abort_if($n === null, 404);

        if ($n->dibaca_at === null) {
            $n->forceFill(['dibaca_at' => now()])->save();
        }

        return response()->json([
            'belum_dibaca' => Notifikasi::query()->where('user_id', $request->user()->id)->whereNull('dibaca_at')->count(),
        ]);
    }
}
