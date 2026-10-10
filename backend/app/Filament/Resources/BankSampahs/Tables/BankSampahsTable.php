<?php

namespace App\Filament\Resources\BankSampahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BankSampahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_bank_sampah')->label('Nama bank sampah')->searchable()->sortable(),
                TextColumn::make('rt')->label('RT')->sortable(),
                TextColumn::make('rw')->label('RW')->sortable(),
                TextColumn::make('alamat')->label('Alamat')->limit(40),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
                TextColumn::make('nasabah_count')->label('Nasabah')->counts('nasabah')->sortable(),
                TextColumn::make('petugas_aktif_count')->label('Petugas aktif')->counts('petugasAktif')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),
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
