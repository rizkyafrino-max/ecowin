<?php

namespace App\Filament\Resources\PenarikanSaldos;

use App\Filament\Actions\DecisionActions;
use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\PenarikanSaldos\Pages\CreatePenarikanSaldo;
use App\Filament\Resources\PenarikanSaldos\Pages\ListPenarikanSaldos;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Services\PenarikanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Status: pending -> approved (saldo dikurangi) -> completed, atau rejected.
 */
class PenarikanSaldoResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = PenarikanSaldo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Penarikan Saldo';

    protected static ?string $modelLabel = 'penarikan saldo';

    protected static ?string $pluralModelLabel = 'penarikan saldo';

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', PenarikanSaldo::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('nasabah_id')->label('Nasabah')
                ->relationship('nasabah', 'nama', fn (Builder $query) => static::scopedNasabahQuery($query))
                ->searchable()->preload()->required()->live()
                ->default(fn () => request()->integer('nasabah_id') ?: null),
            Placeholder::make('saldo_info')->label('Saldo tersedia')
                ->content(fn (Get $get): string => ($n = Nasabah::query()->visibleTo(auth()->user())->find($get('nasabah_id')))
                    ? 'Rp '.number_format($n->saldoTersedia(), 0, ',', '.')
                    : '-'),
            TextInput::make('jumlah')->label('Nominal')->prefix('Rp')->integer()->minValue(config('ecowin.penarikan.minimal'))->maxValue(100000000)->required(),
            Textarea::make('catatan')->maxLength(500),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        $bolehProses = fn (PenarikanSaldo $record): bool => auth()->user()?->can('process', $record) ?? false;

        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nasabah', 'pemroses', 'bankSampah']))
            ->columns([
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable(),
                TextColumn::make('jumlah')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => PenarikanSaldo::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'info', 'completed' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                TextColumn::make('pemroses.nama')->label('Diproses oleh')->placeholder('-'),
                TextColumn::make('catatan')->limit(40)->toggleable(),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->filters([
                SelectFilter::make('status')->options(PenarikanSaldo::STATUSES),
            ])
            ->recordActions([
                DecisionActions::approve(
                    fn (PenarikanSaldo $r): bool => $r->isPending() && $bolehProses($r),
                    fn (PenarikanSaldo $r, ?string $catatan) => app(PenarikanService::class)->setujui(auth()->user(), $r, $catatan),
                ),
                DecisionActions::reject(
                    fn (PenarikanSaldo $r): bool => $r->isPending() && $bolehProses($r),
                    fn (PenarikanSaldo $r, ?string $catatan) => app(PenarikanService::class)->tolak(auth()->user(), $r, (string) $catatan),
                ),
                Action::make('complete')->label('Selesaikan')->icon('heroicon-o-banknotes')->color('success')
                    ->visible(fn (PenarikanSaldo $r): bool => $r->status === PenarikanSaldo::STATUS_APPROVED && $bolehProses($r))
                    ->requiresConfirmation()->modalDescription('Tandai uang sudah diserahkan kepada nasabah.')
                    ->action(function (PenarikanSaldo $record): void {
                        try {
                            app(PenarikanService::class)->selesaikan(auth()->user(), $record);
                            Notification::make()->success()->title('Penarikan selesai.')->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenarikanSaldos::route('/'),
            'create' => CreatePenarikanSaldo::route('/create'),
        ];
    }
}
