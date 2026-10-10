<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas pengguna')->schema([
                TextInput::make('nama')->label('Nama lengkap')->required()->maxLength(150),
                TextInput::make('email')->label('Email login')->email()->disabled()->dehydrated(false)->helperText('Dibuat otomatis dari nama petugas dan RT bank sampah.'),
            ])->columns(2),
            Section::make('Akses sistem')->schema([
                Select::make('role')->label('Peran')->options(['petugas' => 'Petugas', 'admin' => 'Admin'])->default('petugas')->required()->live(),
                Select::make('bank_sampah_id')->label('Bank sampah')->relationship('bankSampah', 'nama_bank_sampah')->searchable()->preload()->required(fn (Get $get): bool => $get('role') === 'petugas')->visible(fn (Get $get): bool => $get('role') === 'petugas'),
                TextInput::make('password')->label('Kata sandi')->password()->revealable()->minLength(8)->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
            ])->columns(2),
        ]);
    }
}
