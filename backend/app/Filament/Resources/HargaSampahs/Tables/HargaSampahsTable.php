<?php

namespace App\Filament\Resources\HargaSampahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HargaSampahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenis_sampah_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kondisi')
                    ->searchable(),
                TextColumn::make('harga_per_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('berlaku_mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('dibuat_oleh')
                    ->numeric()
                    ->sortable(),
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
