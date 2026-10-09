<?php

namespace App\Filament\Resources\TitikBioporis\Schemas;

use App\Models\Nasabah;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TitikBioporiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas titik')->description('Hubungkan titik dengan nasabah dan lokasi bank sampah.')->schema([
                Select::make('nasabah_id')->label('Nasabah')->relationship('nasabah', 'nama')->searchable()->preload()->live()->required()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $nasabah = $state ? Nasabah::find($state) : null;
                        if ($nasabah?->alamat_rt_rw) {
                            $set('alamat_rt_rw', $nasabah->alamat_rt_rw);
                        }
                    }),
                TextInput::make('alamat_rt_rw')->label('Alamat RT/RW')->required()->maxLength(150),
                TextInput::make('deskripsi_lokasi')->label('Keterangan lokasi')->placeholder('Contoh: halaman rumah, taman warga'),
            ])->columns(2),
            Section::make('Koordinat peta')->description('Isi koordinat dari GPS atau klik lokasi pada peta.')->schema([
                TextInput::make('latitude')->label('Lintang')->numeric()->required()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Bujur')->numeric()->required()->minValue(-180)->maxValue(180),
                ViewField::make('ambil_lokasi')->label('')->view('filament.forms.ambil-lokasi')->columnSpanFull(),
            ])->columns(2),
            Section::make('Data penanaman')->schema([
                DatePicker::make('tanggal_tanam')->label('Tanggal tanam')->required()->native(false),
                TextInput::make('jumlah_pipa')->label('Jumlah pipa')->numeric()->minValue(1)->default(1)->required(),
                Select::make('status')->label('Status')->options(['aktif' => 'Aktif', 'penuh' => 'Penuh', 'dikosongkan' => 'Dikosongkan'])->default('aktif')->required(),
                FileUpload::make('foto_path')->label('Foto lokasi')->image()->disk('public')->directory('titik-biopori')->imageEditor(),
            ])->columns(2),
        ]);
    }
}
