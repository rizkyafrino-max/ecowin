<?php

namespace App\Filament\Widgets;

use App\Models\AktivitasBiopori;
use App\Models\TitikBiopori;
use App\Models\User;
use App\Services\LaporanService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Ringkasan jalur Organik di bagian atas halaman Setoran, Aktivitas, dan Lokasi. */
class OrganikStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isStaff();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $organik = app(LaporanService::class)->ringkasan($user)['organik'];

        $pending = AktivitasBiopori::query()->visibleTo($user)->where('status', AktivitasBiopori::STATUS_PENDING)->count();
        $titik = TitikBiopori::query()->visibleTo($user)->get();
        $siapPanen = $titik->filter(fn (TitikBiopori $t) => $t->statusPanenEfektif() === TitikBiopori::PANEN_SIAP)->count();

        return [
            Stat::make('Total organik', number_format($organik['total_berat_kg'], 1, ',', '.').' kg')
                ->description('Estimasi kompos '.number_format($organik['estimasi_kompos_kg'], 1, ',', '.').' kg')->icon('heroicon-o-sparkles'),
            Stat::make('Menunggu verifikasi', number_format($pending))
                ->description($pending > 0 ? 'Aktivitas perlu diperiksa' : 'Semua sudah diperiksa')
                ->color($pending > 0 ? 'warning' : 'success')->icon('heroicon-o-clock'),
            Stat::make('Lokasi aktif', number_format($titik->where('status', 'aktif')->count()))
                ->description($titik->where('bioporiprint', true)->count().' titik BioporiPrint')->icon('heroicon-o-map-pin'),
            Stat::make('Siap panen', number_format($siapPanen))
                ->description($siapPanen > 0 ? 'Perlu ditindaklanjuti' : 'Belum ada yang siap')
                ->color($siapPanen > 0 ? 'warning' : 'success')->icon('heroicon-o-archive-box-arrow-down'),
        ];
    }
}
