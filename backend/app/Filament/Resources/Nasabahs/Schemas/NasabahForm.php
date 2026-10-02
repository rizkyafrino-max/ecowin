<?php

namespace App\Filament\Resources\Nasabahs\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class NasabahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('username')
                    ->default(fn () => 'nasabah-'.Str::lower(Str::random(6)))
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('no_hp')
                    ->required(),
                TextInput::make('alamat_rt_rw')
                    ->required(),
                TextInput::make('nisn_atau_nik'),
                FileUpload::make('foto_ktp_kk_path')->image()->disk('public')->directory('ktp-kk'),
                Select::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn () => auth()->user()->isAdmin())->required(fn () => auth()->user()->isAdmin()),
                Hidden::make('bank_sampah_id')->default(fn () => auth()->user()->bank_sampah_id)->visible(fn () => auth()->user()->isPetugas()),
                TextInput::make('pin')->password()->revealable()->default(fn () => (string) random_int(100000, 999999))->required()->visibleOn('create')->dehydrated(fn ($state): bool => filled($state)),
                Select::make('status_verifikasi')
                    ->options(['pending' => 'Pending', 'verified' => 'Verified'])
                    ->default('pending')
                    ->required(),
                Hidden::make('dibuat_oleh')->default(fn () => auth()->id()),
            ]);
    }
}
