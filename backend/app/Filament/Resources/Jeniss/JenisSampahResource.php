<?php

namespace App\Filament\Resources\Jeniss;

use App\Filament\Resources\Jeniss\Pages\CreateJenisSampah;
use App\Filament\Resources\Jeniss\Pages\EditJenisSampah;
use App\Filament\Resources\Jeniss\Pages\ListJenisSampah;
use App\Filament\Resources\Jeniss\Schemas\JenisSampahForm;
use App\Filament\Resources\Jeniss\Tables\JenisSampahTable;
use App\Models\JenisSampah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JenisSampahResource extends Resource
{
    protected static ?string $model = JenisSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Jenis Sampah';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function form(Schema $schema): Schema
    {
        return JenisSampahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JenisSampahTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisSampah::route('/'),
            'create' => CreateJenisSampah::route('/create'),
            'edit' => EditJenisSampah::route('/{record}/edit'),
        ];
    }
}
