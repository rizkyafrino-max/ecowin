<?php

namespace App\Filament\Resources\BankSampahs;

use App\Filament\Resources\BankSampahs\Pages\CreateBankSampah;
use App\Filament\Resources\BankSampahs\Pages\EditBankSampah;
use App\Filament\Resources\BankSampahs\Pages\ListBankSampahs;
use App\Models\BankSampah;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BankSampahResource extends Resource
{
    protected static ?string $model = BankSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Bank Sampah';

    protected static ?string $modelLabel = 'bank sampah';

    protected static ?string $pluralModelLabel = 'bank sampah';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Bank Sampah')->schema([
                TextInput::make('nama_bank_sampah')->label('Nama')->required()->maxLength(150),
                TextInput::make('kode')->label('Kode')->required()->maxLength(30)->alphaDash()->unique(BankSampah::class, 'kode', ignoreRecord: true),
                Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->default('aktif')->required(),
            ])->columns(3),
            Section::make('Wilayah')->schema([
                TextInput::make('rt')->label('RT')->required()->maxLength(10),
                TextInput::make('rw')->label('RW')->required()->maxLength(10),
                TextInput::make('kelurahan')->maxLength(100),
                TextInput::make('kecamatan')->maxLength(100),
                TextInput::make('kota')->label('Kota/Kabupaten')->maxLength(100),
                Textarea::make('alamat')->required()->maxLength(500)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama_bank_sampah')->label('Nama')->searchable()->sortable(),
                TextColumn::make('rt')->label('RT'),
                TextColumn::make('rw')->label('RW'),
                TextColumn::make('kelurahan')->toggleable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
                TextColumn::make('nasabah_count')->label('Nasabah')->counts('nasabah')->sortable(),
                TextColumn::make('petugas_count')->label('Petugas')->counts('petugas'),
            ])
            ->filters([SelectFilter::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])])
            ->recordActions([EditAction::make()]);
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
