<?php

namespace App\Filament\Resources\AktivitasBioporis\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AktivitasBioporisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable(),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah'),
                TextColumn::make('tanggal_pemasukan')->dateTime()->sortable(),
                TextColumn::make('berat_kg')->suffix(' kg'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'menunggu' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger', default => 'gray'
                }),
                TextColumn::make('catatan_petugas')->limit(40),
                TextColumn::make('pemeriksa.nama')->label('Diperiksa oleh'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('Verifikasi')->visible(fn ($record): bool => $record->status === 'menunggu'),
            ])
            ->toolbarActions([]);
    }
}
