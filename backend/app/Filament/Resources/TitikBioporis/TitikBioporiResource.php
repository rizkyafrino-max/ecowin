<?php

namespace App\Filament\Resources\TitikBioporis;

use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\TitikBioporis\Pages\CreateTitikBiopori;
use App\Filament\Resources\TitikBioporis\Pages\EditTitikBiopori;
use App\Filament\Resources\TitikBioporis\Pages\ListTitikBioporis;
use App\Models\TitikBiopori;
use App\Services\BioporiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
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
 * Organik -> Aktivitas Biopori -> BioporiPrint: lokasi lubang biopori & pemantauan panen.
 */
class TitikBioporiResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = TitikBiopori::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Lokasi & Panen';

    protected static ?string $modelLabel = 'lokasi Biopori';

    protected static ?string $pluralModelLabel = 'lokasi Biopori';

    protected static ?string $cluster = \App\Filament\Clusters\Organik::class;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lokasi')->schema([
                TextInput::make('nama_lokasi')->label('Nama lokasi')->required()->maxLength(150)->placeholder('Contoh: Taman RT 01'),
                Select::make('nasabah_id')->label('Penanggung jawab (nasabah)')
                    ->relationship('nasabah', 'nama', fn (Builder $query) => static::scopedNasabahQuery($query))
                    ->searchable()->preload(),
                TextInput::make('alamat_rt_rw')->label('Alamat RT/RW')->required()->maxLength(150),
                TextInput::make('deskripsi_lokasi')->label('Keterangan')->maxLength(255),
            ])->columns(2),
            Section::make('Koordinat peta')->schema([
                TextInput::make('latitude')->label('Lintang')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Bujur')->numeric()->minValue(-180)->maxValue(180),
                ViewField::make('ambil_lokasi')->label('')->view('filament.forms.ambil-lokasi')->columnSpanFull(),
            ])->columns(2),
            Section::make('BioporiPrint')->description('Pipa biopori hasil 3D printing dari plastik daur ulang.')->schema([
                DatePicker::make('tanggal_tanam')->label('Tanggal dibuat')->required()->maxDate(now()),
                TextInput::make('jumlah_pipa')->label('Jumlah pipa')->integer()->minValue(1)->maxValue(100)->default(1)->required(),
                Toggle::make('bioporiprint')->label('Pipa BioporiPrint (3D print)')->default(true),
                Select::make('status')->options(['aktif' => 'Aktif', 'penuh' => 'Penuh', 'dikosongkan' => 'Dikosongkan'])->default('aktif')->required(),
                FileUpload::make('foto_path')->label('Foto lokasi')->image()->disk('public')->directory('titik-biopori')->maxSize(config('ecowin.upload.foto_max_kb'))
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('estimasi_panen_at')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nasabah', 'bankSampah']))
            ->columns([
                TextColumn::make('nama_lokasi')->label('Lokasi')->searchable()->placeholder(fn (TitikBiopori $r) => $r->alamat_rt_rw),
                TextColumn::make('nasabah.nama')->label('Penanggung jawab')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_tanam')->label('Dibuat')->date('d M Y')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('terakhir_diisi_at')->label('Terakhir diisi')->dateTime('d M Y')->placeholder('Belum'),
                TextColumn::make('estimasi_panen_at')->label('Estimasi panen')->date('d M Y')->placeholder('-')->sortable(),
                TextColumn::make('status_panen')->label('Status panen')->badge()
                    ->state(fn (TitikBiopori $r): string => $r->statusPanenEfektif())
                    ->formatStateUsing(fn (string $state): string => TitikBiopori::STATUS_PANEN[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'siap_panen' => 'warning', 'sudah_panen' => 'success', default => 'gray',
                    }),
                TextColumn::make('hasil_kompos_kg')->label('Hasil kompos')->suffix(' kg')->placeholder('-'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'aktif' => 'success', 'penuh' => 'warning', default => 'gray',
                }),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->filters([
                SelectFilter::make('status')->options(['aktif' => 'Aktif', 'penuh' => 'Penuh', 'dikosongkan' => 'Dikosongkan']),
            ])
            ->recordActions([
                Action::make('panen')->label('Catat panen')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (TitikBiopori $r): bool => $r->statusPanenEfektif() !== TitikBiopori::PANEN_SUDAH && (auth()->user()?->can('update', $r) ?? false))
                    ->schema([TextInput::make('hasil_kompos_kg')->label('Hasil kompos')->suffix('kg')->numeric()->minValue(0)->maxValue(10000)->required()])
                    ->action(function (TitikBiopori $record, array $data): void {
                        app(BioporiService::class)->tandaiPanen(auth()->user(), $record, (float) $data['hasil_kompos_kg']);
                        Notification::make()->success()->title('Panen dicatat.')->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTitikBioporis::route('/'),
            'create' => CreateTitikBiopori::route('/create'),
            'edit' => EditTitikBiopori::route('/{record}/edit'),
        ];
    }
}
