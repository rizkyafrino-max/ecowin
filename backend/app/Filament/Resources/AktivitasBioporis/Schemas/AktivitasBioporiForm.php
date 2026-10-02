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
                Select::make('nasabah_id')->relationship('nasabah', 'nama')->disabled()->dehydrated(false)->visibleOn('edit'),
                DateTimePicker::make('tanggal_pemasukan')->disabled()->dehydrated(false)->visibleOn('edit'),
                TextInput::make('berat_kg')->numeric()->disabled()->dehydrated(false)->visibleOn('edit'),
                Textarea::make('deskripsi')->disabled()->dehydrated(false)->visibleOn('edit'),
                FileUpload::make('foto_bukti_path')->image()->disk('public')->directory('bukti-biopori')->disabled()->dehydrated(false)->visibleOn('edit'),
                Select::make('status')->options(['disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'])->required()->visibleOn('edit'),
                Textarea::make('catatan_petugas')->visibleOn('edit'),
            ]);
    }
}
