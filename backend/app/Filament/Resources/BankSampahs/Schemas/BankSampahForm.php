<?php

namespace App\Filament\Resources\BankSampahs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankSampahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_bank_sampah')->required()->maxLength(255),
                TextInput::make('rt')->required()->maxLength(10),
                TextInput::make('rw')->required()->maxLength(10),
                TextInput::make('alamat')->required()->maxLength(500),
                Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->required(),
            ]);
    }
}
