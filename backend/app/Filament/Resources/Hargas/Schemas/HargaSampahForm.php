<?php

namespace App\Filament\Resources\Hargas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HargaSampahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jenis_sampah_id')->relationship('jenisSampah', 'nama_jenis')->searchable()->preload()->required(),
            TextInput::make('kondisi')->required()->maxLength(100),
            TextInput::make('harga_per_kg')->numeric()->integer()->minValue(0)->prefix('Rp')->required(),
            DateTimePicker::make('berlaku_mulai')->default(now())->required(),
        ]);
    }
}
