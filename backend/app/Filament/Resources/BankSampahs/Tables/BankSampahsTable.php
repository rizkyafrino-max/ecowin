<?php

namespace App\Filament\Resources\BankSampahs\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankSampahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_bank_sampah')->searchable()->sortable(),
                TextColumn::make('rt')->label('RT'),
                TextColumn::make('rw')->label('RW'),
                TextColumn::make('status')->badge(),
                TextColumn::make('petugas.nama')->label('Petugas'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
