<?php

namespace App\Filament\Resources\KoreksiTransaksis;

use App\Filament\Actions\DecisionActions;
use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\KoreksiTransaksis\Pages\ListKoreksiTransaksis;
use App\Models\KoreksiTransaksi;
use App\Services\KoreksiService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Petugas mengajukan koreksi dari tabel transaksi; Admin menyetujui/menolak di sini.
 */
class KoreksiTransaksiResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = KoreksiTransaksi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Koreksi Transaksi';

    protected static ?string $modelLabel = 'koreksi transaksi';

    protected static ?string $pluralModelLabel = 'koreksi transaksi';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', KoreksiTransaksi::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $boleh = fn (KoreksiTransaksi $r): bool => $r->status === KoreksiTransaksi::STATUS_PENDING && (auth()->user()?->can('decide', $r) ?? false);

        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pengaju', 'penyetuju', 'bankSampah']))
            ->columns([
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y H:i'),
                TextColumn::make('tipe_transaksi')->label('Tipe')->badge(),
                TextColumn::make('transaksi_id')->label('Transaksi #'),
                TextColumn::make('data_sebelum.berat_kg')->label('Berat lama')->suffix(' kg'),
                TextColumn::make('data_koreksi.berat_kg')->label('Berat baru')->suffix(' kg'),
                TextColumn::make('alasan')->limit(50)->wrap(),
                TextColumn::make('pengaju.nama')->label('Pengaju'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => KoreksiTransaksi::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                TextColumn::make('penyetuju.nama')->label('Diputuskan oleh')->placeholder('-'),
                TextColumn::make('catatan_admin')->label('Catatan admin')->placeholder('-')->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(KoreksiTransaksi::STATUSES)])
            ->recordActions([
                DecisionActions::approve($boleh, fn (KoreksiTransaksi $r, ?string $c) => app(KoreksiService::class)->setujui(auth()->user(), $r, $c)),
                DecisionActions::reject($boleh, fn (KoreksiTransaksi $r, ?string $c) => app(KoreksiService::class)->tolak(auth()->user(), $r, (string) $c)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKoreksiTransaksis::route('/')];
    }
}
