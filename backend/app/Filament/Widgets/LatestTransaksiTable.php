<?php

namespace App\Filament\Widgets;

use App\Models\TransaksiAnorganik;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Transaksi anorganik terbaru. Petugas hanya melihat Bank Sampah miliknya (scope visibleTo).
 */
class LatestTransaksiTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Transaksi terbaru';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isStaff();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => TransaksiAnorganik::query()->visibleTo(auth()->user())->with(['nasabah', 'hargaSampah.jenisSampah'])->latest('id'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->since()->dateTimeTooltip('d M Y H:i'),
                TextColumn::make('nasabah.nama')->label('Nasabah'),
                TextColumn::make('hargaSampah.jenisSampah.nama_jenis')->label('Jenis'),
                TextColumn::make('berat_kg')->label('Berat')->suffix(' kg')->numeric(2),
                TextColumn::make('nilai_rupiah')->label('Nilai')->money('IDR', locale: 'id')->alignEnd(),
            ])
            ->paginated(false)
            ->defaultSort('id', 'desc');
    }

}
