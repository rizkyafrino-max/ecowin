<?php

namespace App\Filament\Resources\Jeniss\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JenisSampahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kategori_sampah_id')->relationship('kategori', 'nama_kategori')->searchable()->preload()->required(),
            TextInput::make('nama_jenis')->required()->maxLength(255),
        ]);
    }
}
