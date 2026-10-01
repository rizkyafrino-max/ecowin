<?php

namespace App\Filament\Resources\AktivitasBioporis;

use App\Filament\Resources\AktivitasBioporis\Pages\CreateAktivitasBiopori;
use App\Filament\Resources\AktivitasBioporis\Pages\EditAktivitasBiopori;
use App\Filament\Resources\AktivitasBioporis\Pages\ListAktivitasBioporis;
use App\Filament\Resources\AktivitasBioporis\Schemas\AktivitasBioporiForm;
use App\Filament\Resources\AktivitasBioporis\Tables\AktivitasBioporisTable;
use App\Models\AktivitasBiopori;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AktivitasBioporiResource extends Resource
{
    protected static ?string $model = AktivitasBiopori::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user?->isPetugas() ? $query->where('bank_sampah_id', $user->bank_sampah_id) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AktivitasBioporiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AktivitasBioporisTable::configure($table);
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
            'index' => ListAktivitasBioporis::route('/'),
            'create' => CreateAktivitasBiopori::route('/create'),
            'edit' => EditAktivitasBiopori::route('/{record}/edit'),
        ];
    }
}
