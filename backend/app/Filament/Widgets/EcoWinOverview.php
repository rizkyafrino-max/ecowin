<?php

namespace App\Filament\Widgets;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EcoWinOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $bankId = $user->isPetugas() ? $user->bank_sampah_id : null;
        $scope = fn ($query) => $query->when($bankId, fn ($query) => $query->where('bank_sampah_id', $bankId));

        return [
            Stat::make('Nasabah aktif', $scope(Nasabah::query())->where('status_verifikasi', 'verified')->count())
                ->description('Nasabah terdaftar')
                ->color('success'),
            Stat::make('Setoran anorganik', number_format((float) $scope(TransaksiAnorganik::query())->sum('berat_kg'), 2).' kg')
                ->description('Total berat terkumpul')
                ->color('primary'),
            Stat::make('Sampah organik', number_format((float) $scope(TransaksiOrganik::query())->sum('berat_kg'), 2).' kg')
                ->description('Tidak menambah saldo rupiah')
                ->color('info'),
            Stat::make('Biopori menunggu', $scope(AktivitasBiopori::query())->where('status', 'menunggu')->count())
                ->description('Perlu ditinjau petugas')
                ->color('warning'),
        ];
    }
}
