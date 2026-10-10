<?php

namespace App\Filament\Resources\AktivitasBioporis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AktivitasBioporisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nasabah.nama')->label('Nasabah')->searchable()->sortable(),
                TextColumn::make('tanggal_pemasukan')->label('Tanggal')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('deskripsi')->label('Deskripsi')->limit(45),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'disetujui' => 'success', 'ditolak' => 'danger', default => 'warning'
                }),
                TextColumn::make('pemeriksa.nama')->label('Diperiksa oleh')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
