<?php

namespace App\Filament\Resources\JenisSampahs;

use App\Filament\Resources\JenisSampahs\Pages\CreateJenisSampah;
use App\Filament\Resources\JenisSampahs\Pages\EditJenisSampah;
use App\Filament\Resources\JenisSampahs\Pages\ListJenisSampahs;
use App\Models\JenisSampah;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class JenisSampahResource extends Resource
{
    protected static ?string $model = JenisSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Jenis Sampah';

    protected static ?string $modelLabel = 'jenis sampah';

    protected static ?string $pluralModelLabel = 'jenis sampah';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kategori_sampah_id')->label('Kategori')->relationship('kategori', 'nama_kategori')->required()->preload(),
            TextInput::make('nama_jenis')->label('Nama jenis')->required()->maxLength(100),
            Select::make('satuan')->options(['kg' => 'kg'])->default('kg')->required(),
            Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->default('aktif')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama_jenis')->label('Jenis')->searchable()->sortable(),
            TextColumn::make('kategori.nama_kategori')->label('Kategori'),
            TextColumn::make('kategori.tipe')->label('Jalur')->badge(),
            TextColumn::make('satuan'),
            TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
        ])->filters([
            SelectFilter::make('kategori_sampah_id')->label('Kategori')->relationship('kategori', 'nama_kategori'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisSampahs::route('/'),
            'create' => CreateJenisSampah::route('/create'),
            'edit' => EditJenisSampah::route('/{record}/edit'),
        ];
    }
}
