<?php

namespace App\Filament\Resources\TransaksiOrganiks;

use App\Filament\Actions\AjukanKoreksiAction;
use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\TransaksiOrganiks\Pages\CreateTransaksiOrganik;
use App\Filament\Resources\TransaksiOrganiks\Pages\ListTransaksiOrganiks;
use App\Models\TransaksiOrganik;
use App\Services\KomposCalculator;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Jalur Organik: dicatat berat & metode pengolahan, TIDAK menjadi saldo rupiah.
 */
class TransaksiOrganikResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = TransaksiOrganik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Setoran';

    protected static ?string $modelLabel = 'setoran organik';

    protected static ?string $pluralModelLabel = 'setoran organik';

    protected static ?string $cluster = \App\Filament\Clusters\Organik::class;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data setoran organik')->schema([
                Select::make('nasabah_id')->label('Nasabah')
                    ->relationship('nasabah', 'nama', fn (Builder $query) => static::scopedNasabahQuery($query))
                    ->searchable()->preload()->required()
                    ->default(fn () => request()->integer('nasabah_id') ?: null),
                DatePicker::make('tanggal')->default(now())->maxDate(now())->required(),
                TextInput::make('jenis_organik')->label('Jenis sampah organik')->placeholder('Sisa sayur, daun, sisa makanan')->required()->maxLength(100),
                TextInput::make('berat_kg')->label('Berat')->suffix('kg')->numeric()->minValue(0.01)->maxValue(10000)->required()->live(onBlur: true),
                TextInput::make('lokasi')->label('Lokasi pengolahan')->maxLength(255),
                Select::make('metode_pengolahan')->options(TransaksiOrganik::METODE)->default('komposter')->required()->live(),
                Placeholder::make('estimasi')->label('Estimasi hasil kompos')
                    ->content(fn (Get $get): string => number_format(app(KomposCalculator::class)->estimasi((float) $get('berat_kg'), $get('metode_pengolahan')), 2, ',', '.').' kg'),
            ])->columns(2),
            Section::make('Pemeriksaan bahan')->schema([
                Toggle::make('checklist_bebas_plastik')->label('Bebas plastik')->accepted()->required(),
                Toggle::make('checklist_bebas_logam')->label('Bebas logam')->accepted()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nasabah', 'bankSampah']))
            ->columns([
                TextColumn::make('tanggal')->date('d M Y')->sortable(),
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable(),
                TextColumn::make('jenis_organik')->label('Jenis'),
                TextColumn::make('berat_kg')->label('Berat')->suffix(' kg'),
                TextColumn::make('metode_pengolahan')->label('Metode')->badge()->formatStateUsing(fn (?string $state): string => TransaksiOrganik::METODE[$state] ?? (string) $state),
                TextColumn::make('estimasi_kompos_kg')->label('Est. kompos')->suffix(' kg'),
                TextColumn::make('status_pengolahan')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => TransaksiOrganik::STATUS_PENGOLAHAN[$state] ?? (string) $state),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->filters([
                SelectFilter::make('metode_pengolahan')->options(TransaksiOrganik::METODE),
            ])
            ->recordActions([AjukanKoreksiAction::make('organik')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransaksiOrganiks::route('/'),
            'create' => CreateTransaksiOrganik::route('/create'),
        ];
    }
}
