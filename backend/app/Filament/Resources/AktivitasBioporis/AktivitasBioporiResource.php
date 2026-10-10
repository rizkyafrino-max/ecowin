<?php

namespace App\Filament\Resources\AktivitasBioporis;

use App\Filament\Actions\DecisionActions;
use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\AktivitasBioporis\Pages\ListAktivitasBioporis;
use App\Filament\Resources\AktivitasBioporis\Pages\ViewAktivitasBiopori;
use App\Models\AktivitasBiopori;
use App\Services\BioporiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
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
 * Organik -> Aktivitas Biopori (BioporiPrint). Laporan nasabah diverifikasi petugas.
 */
class AktivitasBioporiResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = AktivitasBiopori::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Aktivitas (Verifikasi)';

    protected static ?string $modelLabel = 'aktivitas organik';

    protected static ?string $pluralModelLabel = 'aktivitas organik';

    protected static ?string $cluster = \App\Filament\Clusters\Organik::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', AktivitasBiopori::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan nasabah')->schema([
                TextEntry::make('nasabah.nama')->label('Nasabah'),
                TextEntry::make('metode_pengolahan')->label('Metode')->formatStateUsing(fn (?string $state): string => AktivitasBiopori::METODE[$state] ?? '-'),
                TextEntry::make('titikBiopori.nama_lokasi')->label('Lokasi')->placeholder(fn (AktivitasBiopori $record) => $record->titikBiopori?->alamat_rt_rw ?? '-'),
                TextEntry::make('tanggal_pemasukan')->label('Tanggal & waktu')->dateTime('d M Y H:i'),
                TextEntry::make('jenis_sampah')->label('Jenis sampah')->placeholder('-'),
                TextEntry::make('berat_kg')->label('Perkiraan berat')->suffix(' kg'),
                TextEntry::make('deskripsi')->label('Catatan nasabah')->placeholder('-'),
                ImageEntry::make('foto')->label('Foto bukti')
                    ->state(fn (AktivitasBiopori $record): string => route('admin.berkas.biopori', $record))
                    ->imageHeight(320)->columnSpanFull(),
            ])->columns(3),
            Section::make('Verifikasi')->schema([
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => AktivitasBiopori::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'completed' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                TextEntry::make('pemeriksa.nama')->label('Diperiksa oleh')->placeholder('-'),
                TextEntry::make('waktu_diperiksa')->label('Waktu verifikasi')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('catatan_petugas')->label('Catatan petugas')->placeholder('-')->columnSpanFull(),
                KeyValueEntry::make('data_awal')->label('Data awal (tidak dapat diubah)')->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nasabah', 'titikBiopori', 'pemeriksa']))
            ->columns([
                TextColumn::make('tanggal_pemasukan')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable(),
                TextColumn::make('metode_pengolahan')->label('Metode')->badge()->formatStateUsing(fn (?string $state): string => AktivitasBiopori::METODE[$state] ?? '-'),
                TextColumn::make('titikBiopori.nama_lokasi')->label('Lokasi')->placeholder('-'),
                TextColumn::make('jenis_sampah')->label('Jenis'),
                TextColumn::make('berat_kg')->label('Berat')->suffix(' kg'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => AktivitasBiopori::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'completed' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                TextColumn::make('pemeriksa.nama')->label('Diperiksa oleh')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(AktivitasBiopori::STATUSES)->default(AktivitasBiopori::STATUS_PENDING),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),
                ...static::decisionActions(),
            ]);
    }

    /**
     * @return array<int, Action>
     */
    public static function decisionActions(): array
    {
        $boleh = fn (AktivitasBiopori $r): bool => $r->isPending() && (auth()->user()?->can('verify', $r) ?? false);

        return [
            DecisionActions::approve($boleh, fn (AktivitasBiopori $r, ?string $c) => app(BioporiService::class)->setujui(auth()->user(), $r, $c), 'ACC'),
            DecisionActions::reject($boleh, fn (AktivitasBiopori $r, ?string $c) => app(BioporiService::class)->tolak(auth()->user(), $r, (string) $c)),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAktivitasBioporis::route('/'),
            'view' => ViewAktivitasBiopori::route('/{record}'),
        ];
    }
}
