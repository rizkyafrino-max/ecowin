<?php

namespace App\Filament\Resources\Nasabahs\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NasabahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
<<<<<<< HEAD
                TextInput::make('username')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
=======
>>>>>>> a3b4c50838bdd51191f54f8122435bf578fedcae
                TextInput::make('no_hp')
                    ->required(),
                TextInput::make('alamat_rt_rw')
                    ->required(),
                TextInput::make('nisn_atau_nik'),
                TextInput::make('foto_ktp_kk_path'),
                Hidden::make('bank_sampah_id')->default(fn () => auth()->user()->bank_sampah_id),
                TextInput::make('pin')->password()->revealable()->default(fn () => (string) random_int(100000, 999999))->required(),
                Select::make('status_verifikasi')
                    ->options(['pending' => 'Pending', 'verified' => 'Verified'])
                    ->default('pending')
                    ->required(),
                Hidden::make('dibuat_oleh')->default(fn () => auth()->id()),
            ]);
    }
}
