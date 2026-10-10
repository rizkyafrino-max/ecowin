<?php

namespace App\Filament\Resources\TransaksiOrganiks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksiOrganiksTable
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
                TextColumn::make('jenis_organik')
                    ->searchable(),
                TextColumn::make('berat_kg')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('checklist_bebas_plastik')
                    ->boolean(),
                IconColumn::make('checklist_bebas_logam')
                    ->boolean(),
                TextColumn::make('estimasi_kompos_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dicatat_oleh')
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
