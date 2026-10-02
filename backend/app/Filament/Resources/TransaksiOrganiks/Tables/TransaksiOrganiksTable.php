<?php

namespace App\Filament\Resources\TransaksiOrganiks\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransaksiOrganiksTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            TextColumn::make('nasabah.nama')->searchable()->sortable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('RT'),
            TextColumn::make('jenis_organik')->searchable(),
            TextColumn::make('berat_kg')->suffix(' kg'),
            TextColumn::make('estimasi_kompos_kg')->label('Estimasi kompos')->suffix(' kg'),
            TextColumn::make('pencatat.nama')->label('Petugas'),
        ])->filters([
            SelectFilter::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn () => auth()->user()->isAdmin()),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }
}
