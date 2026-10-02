<?php

namespace App\Filament\Resources\Kategoris;

use App\Filament\Resources\Kategoris\Pages\CreateKategoriSampah;
use App\Filament\Resources\Kategoris\Pages\EditKategoriSampah;
use App\Filament\Resources\Kategoris\Pages\ListKategoriSampah;
use App\Filament\Resources\Kategoris\Schemas\KategoriSampahForm;
use App\Filament\Resources\Kategoris\Tables\KategoriSampahTable;
use App\Models\KategoriSampah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KategoriSampahResource extends Resource
{
    protected static ?string $model = KategoriSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Kategori Sampah';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function form(Schema $schema): Schema
    {
        return KategoriSampahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriSampahTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriSampah::route('/'),
            'create' => CreateKategoriSampah::route('/create'),
            'edit' => EditKategoriSampah::route('/{record}/edit'),
        ];
    }
}
