<?php

namespace App\Filament\Resources\BankSampahs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankSampahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_bank_sampah')->label('Nama bank sampah')->required()->maxLength(150),
                TextInput::make('rt')->label('RT')->required()->maxLength(10),
                TextInput::make('rw')->label('RW')->required()->maxLength(10),
                Textarea::make('alamat')->label('Alamat')->required()->columnSpanFull(),
                Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->default('aktif')->required(),
            ]);
    }
}
