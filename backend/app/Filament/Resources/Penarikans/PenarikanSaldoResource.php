<?php

namespace App\Filament\Resources\Penarikans;

use App\Filament\Resources\Penarikans\Pages\ListPenarikanSaldo;
use App\Filament\Resources\Penarikans\Tables\PenarikanSaldoTable;
use App\Models\PenarikanSaldo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenarikanSaldoResource extends Resource
{
    protected static ?string $model = PenarikanSaldo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Penarikan Saldo';

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
        $query = parent::getEloquentQuery()->with(['nasabah', 'bankSampah', 'pemroses']);
        $user = auth()->user();

        return $user?->isAdmin() ? $query : $query->where('bank_sampah_id', $user?->bank_sampah_id);
    }

    public static function table(Table $table): Table
    {
        return PenarikanSaldoTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListPenarikanSaldo::route('/')];
    }
}
