<?php

namespace App\Http\Controllers;

use App\Http\Resources\NasabahResource;
use App\Models\Nasabah;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScanKartuController extends Controller
{
    public function page(): View
    {
        return view('scan-kartu');
    }

    /**
     * Cari nasabah dari QR. Petugas hanya menemukan nasabah Bank Sampah miliknya.
     * QR bukan pengganti autentikasi: endpoint ini butuh sesi admin/petugas.
     */
    public function lookup(Request $request, string $token): NasabahResource
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32,64}$/', $token) === 1, 404);

        $nasabah = Nasabah::query()->visibleTo($request->user())->with('bankSampah')->where('kartu_qr_token', $token)->firstOrFail();

        return new NasabahResource($nasabah);
    }
}
