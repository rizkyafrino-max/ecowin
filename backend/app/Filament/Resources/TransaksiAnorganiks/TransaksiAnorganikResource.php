<?php

namespace App\Filament\Resources\TransaksiAnorganiks;

use App\Filament\Actions\AjukanKoreksiAction;
use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\TransaksiAnorganiks\Pages\CreateTransaksiAnorganik;
use App\Filament\Resources\TransaksiAnorganiks\Pages\ListTransaksiAnorganiks;
use App\Models\JenisSampah;
use App\Models\TransaksiAnorganik;
use App\Services\PriceResolver;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class TransaksiAnorganikResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = TransaksiAnorganik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Transaksi Anorganik';

    protected static ?string $modelLabel = 'transaksi anorganik';

    protected static ?string $pluralModelLabel = 'transaksi anorganik';

    protected static string|UnitEnum|null $navigationGroup = 'Anorganik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Setoran sampah anorganik')
                ->description('Harga diambil otomatis dari harga yang sedang berlaku (bertingkat sesuai berat). Total = berat × harga/kg.')
                ->schema([
                    Select::make('nasabah_id')->label('Nasabah')
                        ->relationship('nasabah', 'nama', fn (Builder $query) => static::scopedNasabahQuery($query))
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->nama} ({$record->nomor_nasabah})")
                        ->searchable(['nama', 'nomor_nasabah', 'no_hp'])->preload()->required()
                        ->default(fn () => request()->integer('nasabah_id') ?: null),
                    Select::make('jenis_sampah_id')->label('Jenis sampah')
                        ->options(fn () => JenisSampah::query()->where('status', 'aktif')->whereHas('kategori', fn ($q) => $q->where('tipe', 'anorganik'))->orderBy('nama_jenis')->pluck('nama_jenis', 'id'))
                        ->searchable()->required()->live(),
                    TextInput::make('berat_kg')->label('Berat')->suffix('kg')->numeric()->minValue(0.01)->maxValue(10000)->step(0.01)->required()->live(onBlur: true),
                    Placeholder::make('estimasi')->label('Harga berlaku & total')
                        ->content(function (Get $get): string {
                            $jenis = JenisSampah::with('kategori')->find($get('jenis_sampah_id'));
                            $berat = (float) $get('berat_kg');

                            if (! $jenis || $berat <= 0) {
                                return 'Pilih jenis sampah dan isi berat.';
                            }

                            try {
                                $resolver = app(PriceResolver::class);
                                $harga = $resolver->resolve($jenis, $berat);

                                return 'Rp '.number_format($harga->harga_per_kg, 0, ',', '.').'/kg ('.$harga->labelRentang().') → Total Rp '.number_format($resolver->hitungNilai($berat, $harga), 0, ',', '.');
                            } catch (ValidationException $e) {
                                return collect($e->errors())->flatten()->first();
                            }
                        }),
                    FileUpload::make('foto_dokumentasi')->label('Foto dokumentasi (opsional)')->image()->maxSize(config('ecowin.upload.foto_max_kb'))
                        ->storeFiles(false)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nasabah', 'hargaSampah.jenisSampah', 'bankSampah', 'pencatat']))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable(),
                TextColumn::make('hargaSampah.jenisSampah.nama_jenis')->label('Jenis'),
                TextColumn::make('berat_kg')->label('Berat')->suffix(' kg')->sortable(),
                TextColumn::make('harga_per_kg')->label('Harga/kg')->money('IDR', locale: 'id'),
                TextColumn::make('nilai_rupiah')->label('Total')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('pencatat.nama')->label('Dicatat oleh')->toggleable(),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->filters([
                SelectFilter::make('bank_sampah_id')->label('Bank Sampah')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                Filter::make('hari_ini')->label('Hari ini')->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->recordActions([AjukanKoreksiAction::make('anorganik')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransaksiAnorganiks::route('/'),
            'create' => CreateTransaksiAnorganik::route('/create'),
        ];
    }
}
