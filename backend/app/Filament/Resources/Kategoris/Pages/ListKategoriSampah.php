<?php

namespace App\Filament\Resources\Kategoris\Pages;

use App\Filament\Resources\Kategoris\KategoriSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKategoriSampah extends ListRecords
{
    protected static string $resource = KategoriSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
