<?php

namespace App\Filament\Resources\JadwalPengumpulans\Pages;

use App\Filament\Resources\JadwalPengumpulans\JadwalPengumpulanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJadwalPengumpulans extends ListRecords
{
    protected static string $resource = JadwalPengumpulanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah jadwal')];
    }
}
