<?php

namespace App\Filament\Resources\Nasabahs\Schemas;

use App\Models\BankSampah;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NasabahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('username')
                    ->label('Username login')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Dibuat otomatis dari nama nasabah dan RT.'),
                TextInput::make('no_hp')
                    ->required(),
                TextInput::make('alamat_rt_rw')
                    ->label('Alamat RT/RW')
                    ->default(function (): ?string {
                        $bank = auth()->user()->bankSampah;

                        return $bank ? "RT {$bank->rt} / RW {$bank->rw}" : null;
                    })
                    ->readOnly()
                    ->required(),
                TextInput::make('nisn_atau_nik'),
                TextInput::make('foto_ktp_kk_path'),
                Select::make('bank_sampah_id')
                    ->label('Bank sampah / RT')
                    ->relationship('bankSampah', 'nama_bank_sampah')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->user()->bank_sampah_id)
                    ->disabled(fn () => auth()->user()->isPetugas())
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $bank = $state ? BankSampah::find($state) : null;
                        $set('alamat_rt_rw', $bank ? "RT {$bank->rt} / RW {$bank->rw}" : null);
                    })
                    ->required(),
                TextInput::make('pin')->password()->revealable()->default(fn () => (string) random_int(100000, 999999))->required(),
                Select::make('status_verifikasi')
                    ->options(['pending' => 'Pending', 'verified' => 'Verified'])
                    ->default('pending')
                    ->required(),
                Hidden::make('dibuat_oleh')->default(fn () => auth()->id()),
            ]);
    }
}
