<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\LaporanService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PetugasStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isPetugas();
    }

    protected function getStats(): array
    {
        $r = app(LaporanService::class)->ringkasan(auth()->user());

        return [
            Stat::make('Total nasabah', number_format($r['umum']['total_nasabah']))->description('Bank Sampah Anda')->icon('heroicon-o-users'),
            Stat::make('Transaksi hari ini', number_format($r['anorganik']['transaksi_hari_ini']))->description('Setoran anorganik')->icon('heroicon-o-arrows-right-left'),
            Stat::make('Sampah anorganik', number_format($r['anorganik']['total_berat_kg'], 2, ',', '.').' kg')->description('Terbanyak: '.($r['anorganik']['jenis_terbanyak'] ?? '-'))->icon('heroicon-o-archive-box'),
            Stat::make('Sampah organik', number_format($r['organik']['total_berat_kg'], 2, ',', '.').' kg')->description('Estimasi kompos '.number_format($r['organik']['estimasi_kompos_kg'], 2, ',', '.').' kg')->icon('heroicon-o-sparkles'),
            Stat::make('Saldo nasabah', 'Rp '.number_format($r['keuangan']['total_saldo_nasabah'], 0, ',', '.'))->description('Saldo beredar')->icon('heroicon-o-banknotes'),
            Stat::make('Biopori pending', number_format($r['organik']['biopori_pending']))->description('Menunggu verifikasi')->color($r['organik']['biopori_pending'] > 0 ? 'warning' : 'success')->icon('heroicon-o-beaker'),
            Stat::make('Penarikan pending', number_format($r['keuangan']['penarikan_pending']))->description('Menunggu persetujuan')->color($r['keuangan']['penarikan_pending'] > 0 ? 'warning' : 'success')->icon('heroicon-o-clock'),
        ];
    }
}
