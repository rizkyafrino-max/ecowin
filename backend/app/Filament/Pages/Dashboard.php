<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\BerandaHero;
use App\Filament\Widgets\LatestTransaksiTable;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Filament\Widgets\PetugasStatsOverview;
use App\Filament\Widgets\TransaksiChart;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Admin: control center (seluruh sistem). Petugas: operational dashboard (Bank Sampah sendiri).
 */
class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return $this->user()?->isAdmin() ? 'Control Center EcoWin' : 'Dashboard Operasional';
    }

    // Judul halaman sudah diwakili sapaan di BerandaHero.
    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getWidgets(): array
    {
        return $this->user()?->isAdmin()
            ? [BerandaHero::class, AdminStatsOverview::class, TransaksiChart::class, PendingApprovalsTable::class, LatestTransaksiTable::class]
            : [BerandaHero::class, PetugasStatsOverview::class, PendingApprovalsTable::class, TransaksiChart::class, LatestTransaksiTable::class];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
