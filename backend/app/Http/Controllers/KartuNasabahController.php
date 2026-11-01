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

        // QR 600 px (margin 24) agar tetap tajam saat dicetak atau diunduh.
        $qrDataUri = (new Builder(data: $nasabah->kartu_qr_token, size: 600, margin: 24))->build()->getDataUri();

        $bankSampah = $nasabah->bankSampah;
        $bank = collect([$bankSampah?->nama_bank_sampah, $bankSampah?->rt ? 'RT '.$bankSampah->rt.($bankSampah->rw ? '/RW '.$bankSampah->rw : '') : null])->filter()->implode(' · ') ?: 'EcoWin';

        return view('kartu-nasabah', compact('nasabah', 'qrDataUri', 'bank'));
    }
}
