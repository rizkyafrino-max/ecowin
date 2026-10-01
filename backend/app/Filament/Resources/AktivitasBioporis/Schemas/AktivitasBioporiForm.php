<?php

namespace App\Filament\Resources\AktivitasBioporis\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AktivitasBioporiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nasabah_id')->numeric()->required(),
                DateTimePicker::make('tanggal_pemasukan')->required(),
                TextInput::make('berat_kg')->numeric(),
                Textarea::make('deskripsi'),
                FileUpload::make('foto_bukti_path')->image()->required()->disk('public')->directory('bukti-biopori'),
                Select::make('status')->options(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'])->required(),
                Textarea::make('catatan_petugas'),
            ]);
    }
}
