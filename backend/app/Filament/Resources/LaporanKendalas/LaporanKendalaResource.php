<?php

namespace App\Filament\Resources\LaporanKendalas;

use App\Filament\Resources\LaporanKendalas\Pages\ListLaporanKendalas;
use App\Models\LaporanKendala;
use App\Services\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LaporanKendalaResource extends Resource
{
    protected static ?string $model = LaporanKendala::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Laporan Kendala';

    protected static ?string $modelLabel = 'laporan kendala';

    protected static ?string $pluralModelLabel = 'laporan kendala';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pelaporUser', 'peninjau', 'bankSampah']))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('kategori')->badge(),
                TextColumn::make('deskripsi')->limit(60)->wrap(),
                TextColumn::make('pelaporUser.nama')->label('Pelapor'),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->placeholder('-'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'disetujui' => 'success', 'ditolak' => 'danger', default => 'warning',
                }),
                TextColumn::make('catatan_admin')->label('Catatan')->placeholder('-')->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'])])
            ->recordActions([
                Action::make('tinjau')->label('Tinjau')->icon('heroicon-o-check')
                    ->visible(fn (LaporanKendala $r): bool => auth()->user()?->can('update', $r) ?? false)
                    ->schema([
                        Select::make('status')->options(['disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'])->required(),
                        Textarea::make('catatan_admin')->label('Catatan')->maxLength(1000),
                    ])
                    ->action(function (LaporanKendala $record, array $data): void {
                        $before = $record->attributesToArray();
                        $record->forceFill([...$data, 'ditinjau_oleh' => auth()->id()])->save();
                        app(AuditLogger::class)->log('ubah_laporan_kendala', $record, $before, $record->attributesToArray());
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLaporanKendalas::route('/')];
    }
}
