<?php

namespace App\Filament\Resources\TransaksiOrganiks\Schemas;

use App\Models\Nasabah;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TransaksiOrganikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data setoran')
                    ->description('Pilih nasabah dan masukkan hasil setoran organik.')
                    ->schema([
                        Select::make('nasabah_id')
                            ->label('Nasabah')
                            ->relationship('nasabah', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->default(fn () => request()->integer('nasabah_id') ?: null)
                            ->required(),
                        Placeholder::make('bank_sampah_info')
                            ->label('Bank sampah')
                            ->content(fn (Get $get): string => ($nasabah = Nasabah::with('bankSampah')->find($get('nasabah_id')))
                                ? ($nasabah->bankSampah?->nama_bank_sampah ?? 'Belum terhubung')
                                : 'Otomatis setelah nasabah dipilih'),
                        TextInput::make('jenis_organik')
                            ->label('Jenis organik')
                            ->placeholder('Contoh: sisa sayur, daun, atau makanan')
                            ->required(),
                        TextInput::make('berat_kg')
                            ->label('Berat setoran')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('estimasi_kompos_kg')
                            ->label('Perkiraan kompos')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])->columns(2),
                Section::make('Pemeriksaan bahan')
                    ->description('Pastikan setoran bebas dari bahan yang mengganggu proses pengolahan.')
                    ->schema([
                        Toggle::make('checklist_bebas_plastik')->label('Bebas plastik')->default(false)->required(),
                        Toggle::make('checklist_bebas_logam')->label('Bebas logam')->default(false)->required(),
                    ])->columns(2),
                Hidden::make('bank_sampah_id'),
                Hidden::make('dicatat_oleh'),
            ]);
    }
}
