<?php

namespace App\Filament\Resources\Nasabahs;

use App\Filament\Resources\Nasabahs\Pages\CreateNasabah;
use App\Filament\Resources\Nasabahs\Pages\EditNasabah;
use App\Filament\Resources\Nasabahs\Pages\ListNasabahs;
use App\Filament\Resources\Nasabahs\Schemas\NasabahForm;
use App\Filament\Resources\Nasabahs\Tables\NasabahsTable;
use App\Models\Nasabah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NasabahResource extends Resource
{
    protected static ?string $model = Nasabah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user?->isAdmin() ? $query : $query->where('bank_sampah_id', $user?->bank_sampah_id);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isPetugas();
    }

    public static function form(Schema $schema): Schema
    {
        return NasabahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NasabahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNasabahs::route('/'),
            'create' => CreateNasabah::route('/create'),
            'edit' => EditNasabah::route('/{record}/edit'),
        ];
    }
}
