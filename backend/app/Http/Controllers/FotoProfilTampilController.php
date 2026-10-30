<?php

namespace App\Http\Controllers;

use App\Services\FotoProfilService;
use Illuminate\Http\Response;

/**
 * Menyajikan foto profil lewat kode acak 32 karakter (tidak bisa ditebak). Kode berubah setiap foto diganti, sehingga
 * aman di-cache lama. Hanya JPEG hasil olahan server yang pernah disajikan.
 */
class FotoProfilTampilController extends Controller
{
    public function __invoke(string $kode, FotoProfilService $foto): Response
    {
        $isi = $foto->baca($kode);
        abort_if($isi === null, 404);

        return response($isi, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
