<?php

namespace App\Services;

/**
 * Estimasi hasil kompos = berat bahan organik x rasio (dapat dikonfigurasi di config/ecowin.php / .env).
 */
class KomposCalculator
{
    public function estimasi(float $beratKg, ?string $metode = null): float
    {
        $rasio = config("ecowin.kompos.rasio_per_metode.{$metode}") ?? config('ecowin.kompos.rasio_default');

        return round($beratKg * (float) $rasio, 2);
    }
}
