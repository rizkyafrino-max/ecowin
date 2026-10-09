<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksiAnorganiksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nasabah_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bank_sampah_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('harga_sampah_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('berat_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nilai_rupiah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dicatat_oleh')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('foto_dokumentasi_path')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
