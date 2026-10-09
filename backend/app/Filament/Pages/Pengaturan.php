<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Pengaturan sistem (hanya Admin, read-only). Nilai berasal dari config/ecowin.php
 * dan diubah lewat file .env; tidak ada rahasia (secret/token) yang ditampilkan.
 */
class Pengaturan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $title = 'Pengaturan';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.pengaturan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin() && $user->isActive();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function kelompok(): array
    {
        $kg = fn (float $v): string => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',').' × berat';

        $kompos = ['Rasio bawaan' => $kg((float) config('ecowin.kompos.rasio_default'))];
        foreach ((array) config('ecowin.kompos.rasio_per_metode') as $metode => $rasio) {
            $kompos['Metode '.ucfirst((string) $metode)] = $kg((float) $rasio);
        }

        return [
            'Estimasi kompos' => $kompos,
            'Biopori' => [
                'Estimasi siap panen' => config('ecowin.biopori.hari_panen').' hari setelah terakhir diisi',
            ],
            'Penarikan saldo' => [
                'Nominal minimal' => 'Rp '.number_format((int) config('ecowin.penarikan.minimal'), 0, ',', '.'),
            ],
            'Unggah foto' => [
                'Ukuran maksimal' => number_format((int) config('ecowin.upload.foto_max_kb') / 1024, 1, ',', '.').' MB',
                'Dimensi maksimal' => number_format((int) config('ecowin.upload.foto_max_dimensi'), 0, ',', '.').' px',
                'Penyimpanan' => config('ecowin.upload.disk') === 'local' ? 'Privat (tidak dapat diakses publik)' : (string) config('ecowin.upload.disk'),
            ],
            'Sesi aplikasi Android' => [
                'Access token' => number_format((int) config('ecowin.tokens.access_ttl') / 60, 0, ',', '.').' jam',
                'Refresh token' => number_format((int) config('ecowin.tokens.refresh_ttl') / 60 / 24, 0, ',', '.').' hari',
            ],
            'Login Google' => [
                'Client ID' => config('services.google.client_id') ? 'Terkonfigurasi' : 'Belum diisi',
                'Client secret' => config('services.google.client_secret') ? 'Terkonfigurasi' : 'Belum diisi',
                'Redirect URI' => (string) config('services.google.redirect'),
            ],
        ];
    }
}
