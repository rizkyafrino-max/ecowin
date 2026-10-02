<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransaksiAnorganiksTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            TextColumn::make('nasabah.nama')->searchable()->sortable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('RT'),
            TextColumn::make('hargaSampah.jenisSampah.nama_jenis')->label('Jenis'),
            TextColumn::make('berat_kg')->suffix(' kg'),
            TextColumn::make('nilai_rupiah')->money('IDR')->sortable(),
            TextColumn::make('pencatat.nama')->label('Petugas'),
        ])->filters([
            SelectFilter::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn () => auth()->user()->isAdmin()),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }
}
