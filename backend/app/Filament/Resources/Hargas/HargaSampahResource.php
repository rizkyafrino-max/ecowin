<?php

namespace App\Filament\Resources\Hargas;

use App\Filament\Resources\Hargas\Pages\CreateHargaSampah;
use App\Filament\Resources\Hargas\Pages\ListHargaSampah;
use App\Filament\Resources\Hargas\Schemas\HargaSampahForm;
use App\Filament\Resources\Hargas\Tables\HargaSampahTable;
use App\Models\HargaSampah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HargaSampahResource extends Resource
{
    protected static ?string $model = HargaSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Harga Sampah';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isPetugas();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return HargaSampahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HargaSampahTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHargaSampah::route('/'),
            'create' => CreateHargaSampah::route('/create'),
        ];
    }
}
