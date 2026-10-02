<?php

namespace App\Filament\Resources\TransaksiOrganiks;

use App\Filament\Resources\TransaksiOrganiks\Pages\ListTransaksiOrganiks;
use App\Filament\Resources\TransaksiOrganiks\Tables\TransaksiOrganiksTable;
use App\Models\TransaksiOrganik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransaksiOrganikResource extends Resource
{
    protected static ?string $model = TransaksiOrganik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Transaksi Organik';

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
        $query = parent::getEloquentQuery()->with(['nasabah', 'bankSampah', 'pencatat']);
        $user = auth()->user();

        return $user?->isAdmin() ? $query : $query->where('bank_sampah_id', $user?->bank_sampah_id);
    }

    public static function table(Table $table): Table
    {
        return TransaksiOrganiksTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListTransaksiOrganiks::route('/')];
    }
}
