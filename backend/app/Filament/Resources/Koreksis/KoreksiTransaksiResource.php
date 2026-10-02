<?php

namespace App\Filament\Resources\Koreksis;

use App\Filament\Resources\Koreksis\Pages\ListKoreksiTransaksi;
use App\Filament\Resources\Koreksis\Tables\KoreksiTransaksiTable;
use App\Models\KoreksiTransaksi;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KoreksiTransaksiResource extends Resource
{
    protected static ?string $model = KoreksiTransaksi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Koreksi Transaksi';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isPetugas();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pengaju', 'penyetuju']);
        $user = auth()->user();

        if ($user?->isPetugas()) {
            $bankId = $user->bank_sampah_id;
            $query->where(function (Builder $builder) use ($bankId): void {
                $builder->where(function (Builder $builder) use ($bankId): void {
                    $builder->where('tipe_transaksi', 'anorganik')
                        ->whereIn('transaksi_id', TransaksiAnorganik::query()->select('id')->where('bank_sampah_id', $bankId));
                })->orWhere(function (Builder $builder) use ($bankId): void {
                    $builder->where('tipe_transaksi', 'organik')
                        ->whereIn('transaksi_id', TransaksiOrganik::query()->select('id')->where('bank_sampah_id', $bankId));
                });
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return KoreksiTransaksiTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListKoreksiTransaksi::route('/')];
    }
}
