<?php

namespace App\Filament\Resources\TransaksiAnorganiks;

use App\Filament\Resources\TransaksiAnorganiks\Pages\ListTransaksiAnorganiks;
use App\Filament\Resources\TransaksiAnorganiks\Tables\TransaksiAnorganiksTable;
use App\Models\TransaksiAnorganik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransaksiAnorganikResource extends Resource
{
    protected static ?string $model = TransaksiAnorganik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Transaksi Anorganik';

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
        $query = parent::getEloquentQuery()->with(['nasabah', 'bankSampah', 'hargaSampah.jenisSampah', 'pencatat']);
        $user = auth()->user();

        return $user?->isAdmin() ? $query : $query->where('bank_sampah_id', $user?->bank_sampah_id);
    }

    public static function table(Table $table): Table
    {
        return TransaksiAnorganiksTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListTransaksiAnorganiks::route('/')];
    }
}
