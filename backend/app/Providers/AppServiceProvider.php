<?php

namespace App\Providers;

use App\Models\HargaSampah;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Observers\HargaSampahObserver;
use App\Observers\KoreksiTransaksiObserver;
use App\Observers\NasabahObserver;
use App\Observers\PenarikanSaldoObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        HargaSampah::observe(HargaSampahObserver::class);
        Nasabah::observe(NasabahObserver::class);
        KoreksiTransaksi::observe(KoreksiTransaksiObserver::class);
        PenarikanSaldo::observe(PenarikanSaldoObserver::class);
    }
}