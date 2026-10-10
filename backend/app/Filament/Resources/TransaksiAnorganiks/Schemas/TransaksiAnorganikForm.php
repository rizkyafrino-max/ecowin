<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransaksiAnorganikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data setoran')
                    ->description('Pilih nasabah dan harga sampah yang berlaku. Nilai setoran dihitung otomatis.')
                    ->schema([
                        Select::make('nasabah_id')
                            ->label('Nasabah')
                            ->relationship('nasabah', 'nama')
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->integer('nasabah_id') ?: null)
                            ->required(),
                        Select::make('harga_sampah_id')
                            ->label('Jenis dan harga sampah')
                            ->relationship('hargaSampah', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => ($record->jenisSampah?->nama_jenis ?? 'Jenis sampah').' — Rp '.number_format($record->harga_per_kg, 0, ',', '.').' / kg ('.ucfirst($record->kondisi).')')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('berat_kg')
                            ->label('Berat setoran')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('nilai_rupiah')
                            ->label('Nilai setoran')
                            ->prefix('Rp')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dihitung otomatis setelah data disimpan.'),
                        TextInput::make('foto_dokumentasi_path')
                            ->label('Foto dokumentasi')
                            ->placeholder('Opsional'),
                    ])->columns(2),
                Hidden::make('bank_sampah_id'),
                Hidden::make('dicatat_oleh'),
            ]);
    }
}
