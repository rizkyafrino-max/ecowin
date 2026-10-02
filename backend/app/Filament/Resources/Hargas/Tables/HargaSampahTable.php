<?php

namespace App\Filament\Resources\Hargas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HargaSampahTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('jenisSampah.nama_jenis')->label('Jenis sampah')->searchable()->sortable(),
            TextColumn::make('kondisi')->badge(),
            TextColumn::make('harga_per_kg')->money('IDR')->sortable(),
            TextColumn::make('berlaku_mulai')->dateTime()->sortable(),
            TextColumn::make('pembuat.nama')->label('Dibuat oleh'),
        ])->defaultSort('berlaku_mulai', 'desc')->toolbarActions([]);
    }
}
