<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\LaporanService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        $r = app(LaporanService::class)->ringkasan(auth()->user());

        return [
            Stat::make('Bank Sampah', number_format($r['umum']['total_bank_sampah']))->description('RT/RW terdaftar')->icon('heroicon-o-building-storefront'),
            Stat::make('Petugas', number_format($r['umum']['total_petugas']))->description('Pengelola Bank Sampah')->icon('heroicon-o-user-group'),
            Stat::make('Nasabah', number_format($r['umum']['total_nasabah']))->description('Seluruh RT')->icon('heroicon-o-users'),
            Stat::make('Total transaksi', number_format($r['anorganik']['jumlah_transaksi']))->description($r['anorganik']['transaksi_hari_ini'].' transaksi hari ini')->icon('heroicon-o-arrows-right-left'),
            Stat::make('Sampah anorganik', number_format($r['anorganik']['total_berat_kg'], 2, ',', '.').' kg')->description('Terbanyak: '.($r['anorganik']['jenis_terbanyak'] ?? '-'))->icon('heroicon-o-archive-box'),
            Stat::make('Sampah organik', number_format($r['organik']['total_berat_kg'], 2, ',', '.').' kg')->description('Estimasi kompos '.number_format($r['organik']['estimasi_kompos_kg'], 2, ',', '.').' kg')->icon('heroicon-o-sparkles'),
            Stat::make('Total saldo', 'Rp '.number_format($r['keuangan']['total_saldo_nasabah'], 0, ',', '.'))->description('Saldo beredar nasabah')->icon('heroicon-o-banknotes'),
            Stat::make('Aktivitas Biopori', number_format($r['organik']['jumlah_aktivitas_biopori']))->description($r['organik']['biopori_pending'].' menunggu verifikasi')->color($r['organik']['biopori_pending'] > 0 ? 'warning' : 'success')->icon('heroicon-o-beaker'),
            Stat::make('Pengajuan penarikan', number_format($r['keuangan']['penarikan_pending']))->description('Status pending')->color($r['keuangan']['penarikan_pending'] > 0 ? 'warning' : 'success')->icon('heroicon-o-clock'),
        ];
    }
}
