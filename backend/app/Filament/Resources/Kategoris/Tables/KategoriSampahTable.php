<?php

namespace App\Filament\Resources\Kategoris\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriSampahTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama_kategori')->searchable()->sortable(),
            TextColumn::make('jenisSampah_count')->counts('jenisSampah')->label('Jumlah jenis'),
        ])->recordActions([EditAction::make()])->toolbarActions([]);
    }
}
