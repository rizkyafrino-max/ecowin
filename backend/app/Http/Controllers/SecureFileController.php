<?php

namespace App\Http\Controllers;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan berkas privat (foto bukti, KTP/KK) hanya kepada yang berhak.
 */
class SecureFileController extends Controller
{
    public function biopori(AktivitasBiopori $aktivitas): StreamedResponse
    {
        $this->authorize('view', $aktivitas);

        return $this->stream($aktivitas->foto_bukti_path);
    }

    public function identitas(Nasabah $nasabah): StreamedResponse
    {
        $this->authorize('update', $nasabah);

        return $this->stream($nasabah->foto_ktp_kk_path);
    }

    private function stream(?string $path): StreamedResponse
    {
        $disk = Storage::disk(config('ecowin.upload.disk'));

        abort_unless($path && ! str_contains($path, '..') && $disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'",
        ]);
    }
}
