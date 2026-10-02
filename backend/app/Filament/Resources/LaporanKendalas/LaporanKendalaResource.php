<?php

namespace App\Filament\Resources\LaporanKendalas;

use App\Filament\Resources\LaporanKendalas\Pages\ListLaporanKendalas;
use App\Filament\Resources\LaporanKendalas\Tables\LaporanKendalasTable;
use App\Models\LaporanKendala;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanKendalaResource extends Resource
{
    protected static ?string $model = LaporanKendala::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Laporan Kendala';

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
        $query = parent::getEloquentQuery()->with(['bankSampah', 'pelaporNasabah', 'pelaporUser', 'peninjau']);
        $user = auth()->user();

        return $user?->isAdmin() ? $query : $query->where('bank_sampah_id', $user?->bank_sampah_id);
    }

    public static function table(Table $table): Table
    {
        return LaporanKendalasTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListLaporanKendalas::route('/')];
    }
}
