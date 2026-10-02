<?php

namespace App\Filament\Resources\Jeniss\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JenisSampahTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama_jenis')->searchable()->sortable(),
            TextColumn::make('kategori.nama_kategori')->label('Kategori')->sortable(),
            TextColumn::make('hargaSampah_count')->counts('hargaSampah')->label('Riwayat harga'),
        ])->recordActions([EditAction::make()])->toolbarActions([]);
    }
}
