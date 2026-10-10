<?php

namespace App\Filament\Resources\HargaSampahs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HargaSampahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('jenis_sampah_id')
                    ->required()
                    ->numeric(),
                TextInput::make('kondisi')
                    ->required(),
                TextInput::make('harga_per_kg')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('berlaku_mulai')
                    ->required(),
                TextInput::make('dibuat_oleh')
                    ->required()
                    ->numeric(),
            ]);
    }
}
