<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Contracts\View\View;

class KartuNasabahController extends Controller
{
    /**
     * QR berisi token acak saja (tanpa NIK/nama/no HP).
     */
    public function show(Nasabah $nasabah): View
    {
        $this->authorize('update', $nasabah);

        $qrDataUri = (new Builder(data: $nasabah->kartu_qr_token, size: 260, margin: 12))->build()->getDataUri();

        return view('kartu-nasabah', compact('nasabah', 'qrDataUri'));
    }
}
