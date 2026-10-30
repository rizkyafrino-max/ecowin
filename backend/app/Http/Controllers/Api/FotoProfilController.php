<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use App\Services\FotoProfilService;
use Illuminate\Http\Request;

/** Ganti/hapus foto profil milik sendiri (semua role). Foto orang lain tidak bisa diubah dari sini. */
class FotoProfilController extends Controller
{
    public function store(Request $request, FotoProfilService $foto, AuditLogger $audit): UserResource
    {
        $request->validate(['foto' => ['required', 'file', 'max:'.(FotoProfilService::MAKS_BYTE / 1024), 'mimetypes:image/jpeg,image/png,image/webp']]);

        $user = $request->user();
        $foto->simpan($user, (string) $request->file('foto')->get());
        $audit->log('ganti_foto_profil', $user, null, null, $user);

        return new UserResource($user->fresh()->load('nasabah.bankSampah'));
    }

    public function destroy(Request $request, FotoProfilService $foto, AuditLogger $audit): UserResource
    {
        $user = $request->user();
        $foto->hapus($user);
        $audit->log('hapus_foto_profil', $user, null, null, $user);

        return new UserResource($user->fresh()->load('nasabah.bankSampah'));
    }
}
