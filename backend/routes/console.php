<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
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

/*
| Notifikasi dan riwayat pengumuman (yang sudah terkirim) lebih dari 30 hari dihapus otomatis (dijadwalkan harian; butuh
| `php artisan schedule:run` tiap menit di server). Jejak pengiriman tetap ada di audit log.
*/
Artisan::command('ecowin:bersihkan-notifikasi', function () {
    $batas = now()->subDays(30);
    $n = DB::table('notifikasi')->where('created_at', '<', $batas)->delete();
    $p = DB::table('pengumuman')->whereNotNull('terkirim_at')->where('terkirim_at', '<', $batas)->delete();
    $this->info("Notifikasi lama dihapus: {$n}; riwayat pengumuman lama dihapus: {$p}");
})->purpose('Hapus notifikasi dan riwayat pengumuman lebih dari 30 hari');

Schedule::command('ecowin:bersihkan-notifikasi')->dailyAt('03:15');

/*
| Pengumuman terjadwal dikirim otomatis saat waktunya tiba (butuh `php artisan schedule:run` tiap menit di server).
*/
Artisan::command('ecowin:kirim-pengumuman', function () {
    $n = app(\App\Services\PengumumanService::class)->kirimYangJatuhTempo();
    $this->info("Pengumuman terkirim: {$n}");
})->purpose('Kirim pengumuman terjadwal yang sudah jatuh waktu');

Schedule::command('ecowin:kirim-pengumuman')->everyMinute()->withoutOverlapping();

/*
| Pengingat jadwal pengumpulan sampah per bank sampah (diatur Admin), dikirim otomatis sebelum kegiatan dimulai.
*/
Artisan::command('ecowin:kirim-jadwal-pengumpulan', function () {
    $n = app(\App\Services\JadwalPengumpulanService::class)->kirimYangJatuhTempo();
    $this->info("Pengingat jadwal terkirim: {$n}");
})->purpose('Kirim pengingat jadwal pengumpulan sampah yang jatuh waktu');

Schedule::command('ecowin:kirim-jadwal-pengumpulan')->everyMinute()->withoutOverlapping();
