<?php

namespace App\Filament\Resources\PenarikanSaldos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PenarikanSaldoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('nasabah_id')->label('Nasabah')->relationship('nasabah', 'nama')->searchable()->preload()->default(fn () => request()->integer('nasabah_id') ?: null)->required(),
                TextInput::make('bank_sampah_id')
                    ->required()
                    ->numeric(),
                TextInput::make('jumlah')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'selesai' => 'Selesai'])
                    ->default('pending')
                    ->required(),
                TextInput::make('diproses_oleh')
                    ->numeric(),
            ]);
    }
}
