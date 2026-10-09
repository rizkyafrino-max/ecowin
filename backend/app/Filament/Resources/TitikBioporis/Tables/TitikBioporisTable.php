<?php

namespace App\Filament\Resources\TitikBioporis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TitikBioporisTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nasabah.nama')->label('Nasabah')->searchable()->sortable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah')->searchable()->toggleable(),
            TextColumn::make('alamat_rt_rw')->label('Lokasi')->searchable()->limit(35),
            TextColumn::make('latitude')->label('Lintang')->toggleable(),
            TextColumn::make('longitude')->label('Bujur')->toggleable(),
            TextColumn::make('tanggal_tanam')->label('Tanggal tanam')->date('d M Y')->sortable(),
            TextColumn::make('jumlah_pipa')->label('Pipa')->sortable(),
            TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                'aktif' => 'success', 'penuh' => 'warning', default => 'gray',
            }),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(['aktif' => 'Aktif', 'penuh' => 'Penuh', 'dikosongkan' => 'Dikosongkan']),
        ])->recordActions([EditAction::make()])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
