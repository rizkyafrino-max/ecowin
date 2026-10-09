<?php

namespace App\Filament\Resources\KategoriSampahs;

use App\Filament\Resources\KategoriSampahs\Pages\CreateKategoriSampah;
use App\Filament\Resources\KategoriSampahs\Pages\EditKategoriSampah;
use App\Filament\Resources\KategoriSampahs\Pages\ListKategoriSampahs;
use App\Models\KategoriSampah;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KategoriSampahResource extends Resource
{
    protected static ?string $model = KategoriSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Kategori Sampah';

    protected static ?string $modelLabel = 'kategori sampah';

    protected static ?string $pluralModelLabel = 'kategori sampah';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_kategori')->label('Nama kategori')->required()->maxLength(100),
            Select::make('tipe')->label('Jalur')->options(KategoriSampah::TIPE)->required()->default('anorganik'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama_kategori')->label('Kategori')->searchable()->sortable(),
            TextColumn::make('tipe')->label('Jalur')->badge()->formatStateUsing(fn (string $state): string => KategoriSampah::TIPE[$state] ?? $state)
                ->color(fn (string $state): string => $state === 'organik' ? 'success' : 'info'),
            TextColumn::make('jenis_sampah_count')->label('Jenis')->counts('jenisSampah'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriSampahs::route('/'),
            'create' => CreateKategoriSampah::route('/create'),
            'edit' => EditKategoriSampah::route('/{record}/edit'),
        ];
    }
}
