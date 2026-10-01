<?php

namespace App\Filament\Resources\BankSampahs;

use App\Filament\Resources\BankSampahs\Pages\CreateBankSampah;
use App\Filament\Resources\BankSampahs\Pages\EditBankSampah;
use App\Filament\Resources\BankSampahs\Pages\ListBankSampahs;
use App\Filament\Resources\BankSampahs\Schemas\BankSampahForm;
use App\Filament\Resources\BankSampahs\Tables\BankSampahsTable;
use App\Models\BankSampah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankSampahResource extends Resource
{
    protected static ?string $model = BankSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function form(Schema $schema): Schema
    {
        return BankSampahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankSampahsTable::configure($table);
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
            'index' => ListBankSampahs::route('/'),
            'create' => CreateBankSampah::route('/create'),
            'edit' => EditBankSampah::route('/{record}/edit'),
        ];
    }
}
