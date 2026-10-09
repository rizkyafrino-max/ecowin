<?php

namespace App\Filament\Resources\HargaSampahs;

use App\Filament\Resources\HargaSampahs\Pages\CreateHargaSampah;
use App\Filament\Resources\HargaSampahs\Pages\EditHargaSampah;
use App\Filament\Resources\HargaSampahs\Pages\ListHargaSampahs;
use App\Models\HargaSampah;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Harga dinamis & bertingkat. Tidak ada harga yang di-hardcode di aplikasi.
 */
class HargaSampahResource extends Resource
{
    protected static ?string $model = HargaSampah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Harga Sampah';

    protected static ?string $modelLabel = 'harga sampah';

    protected static ?string $pluralModelLabel = 'harga sampah';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jenis & tingkatan berat')->description('Contoh harga bertingkat: 0–5 kg Rp X, 5–10 kg Rp Y, ≥10 kg Rp Z.')->schema([
                Select::make('jenis_sampah_id')->label('Jenis sampah')
                    ->relationship('jenisSampah', 'nama_jenis', fn (Builder $query) => $query->whereHas('kategori', fn ($k) => $k->where('tipe', 'anorganik')))
                    ->searchable()->preload()->required(),
                TextInput::make('kondisi')->default('Utuh')->required()->maxLength(50),
                TextInput::make('minimal_berat')->label('Berat minimal')->suffix('kg')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('maksimal_berat')->label('Berat maksimal')->suffix('kg')->numeric()->gt('minimal_berat')->helperText('Kosongkan bila tanpa batas atas.'),
            ])->columns(2),
            Section::make('Harga & masa berlaku')->schema([
                TextInput::make('harga_per_kg')->label('Harga per kg')->prefix('Rp')->integer()->minValue(0)->maxValue(10000000)->required(),
                DateTimePicker::make('berlaku_mulai')->default(now())->required(),
                DateTimePicker::make('berlaku_sampai')->after('berlaku_mulai'),
                Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->default('aktif')->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('berlaku_mulai', 'desc')
            ->columns([
                TextColumn::make('jenisSampah.nama_jenis')->label('Jenis')->searchable()->sortable(),
                TextColumn::make('kondisi'),
                TextColumn::make('rentang')->label('Rentang berat')->state(fn (HargaSampah $record): string => $record->labelRentang()),
                TextColumn::make('harga_per_kg')->label('Harga/kg')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('berlaku_mulai')->dateTime('d M Y')->sortable(),
                TextColumn::make('berlaku_sampai')->dateTime('d M Y')->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
                TextColumn::make('pembuat.nama')->label('Dibuat oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis_sampah_id')->label('Jenis')->relationship('jenisSampah', 'nama_jenis'),
                SelectFilter::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHargaSampahs::route('/'),
            'create' => CreateHargaSampah::route('/create'),
            'edit' => EditHargaSampah::route('/{record}/edit'),
        ];
    }
}
