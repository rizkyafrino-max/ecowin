<?php

namespace App\Filament\Resources\Pengumumans\Pages;

use App\Filament\Resources\Pengumumans\PengumumanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengumumans extends ListRecords
{
    protected static string $resource = PengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat pengumuman')];
    }
}
