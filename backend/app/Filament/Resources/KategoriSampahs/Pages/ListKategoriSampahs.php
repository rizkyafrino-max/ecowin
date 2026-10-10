<?php

namespace App\Filament\Resources\KategoriSampahs\Pages;

use App\Filament\Resources\KategoriSampahs\KategoriSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKategoriSampahs extends ListRecords
{
    protected static string $resource = KategoriSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
