<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Pindahkan berkas sensitif lama (KTP/KK, bukti biopori, dokumentasi) dari disk
| publik ke disk privat. Jalankan sekali setelah update: php artisan ecowin:amankan-berkas
*/
Artisan::command('ecowin:amankan-berkas', function () {
    $public = Storage::disk('public');
    $private = Storage::disk(config('ecowin.upload.disk'));
    $dipindah = 0;

    foreach (['ktp-kk', 'identitas', 'bukti-biopori', 'dokumentasi-anorganik'] as $folder) {
        foreach ($public->allFiles($folder) as $path) {
            if (! $private->exists($path)) {
                $private->put($path, $public->get($path));
            }

            $public->delete($path);
            $dipindah++;
        }
    }

    $this->info("{$dipindah} berkas dipindahkan ke penyimpanan privat.");
})->purpose('Pindahkan berkas sensitif dari storage publik ke privat');
