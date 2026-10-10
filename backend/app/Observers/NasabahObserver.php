<?php

namespace App\Observers;

use App\Models\Nasabah;
use Illuminate\Support\Str;

class NasabahObserver
{
    /**
     * Token QR acak & tidak bisa ditebak (bukan data pribadi).
     */
    public function creating(Nasabah $nasabah): void
    {
        if (! $nasabah->kartu_qr_token) {
            $nasabah->kartu_qr_token = Str::random(48);
        }
    }
}
