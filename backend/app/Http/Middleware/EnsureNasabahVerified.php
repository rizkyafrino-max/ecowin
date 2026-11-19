<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nasabah yang mendaftar sendiri berstatus "pending" sampai petugas Bank Sampah memverifikasinya. Selama itu semua rute data
 * dan fitur layanan (saldo, riwayat, harga, peta Biopori, kartu QR, penarikan, lapor aktivitas) ditolak di server, jadi tidak
 * bisa dilewati dengan memanggil API langsung. Admin dan petugas tidak terpengaruh. Pemakaian: ->middleware('nasabah.terverifikasi').
 *
 * Tetap terbuka untuk akun yang belum diverifikasi: profil, notifikasi, lapor bug, hapus akun, dan keluar.
 */
class EnsureNasabahVerified
{
    public const KODE = 'belum_diverifikasi';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isNasabah() && $user->nasabah?->status_verifikasi !== 'verified') {
            return response()->json([
                'message' => 'Akunmu belum diverifikasi petugas Bank Sampah, jadi bagian ini belum bisa dibuka.',
                'code' => self::KODE,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
