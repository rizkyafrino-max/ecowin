<?php

namespace App\Filament\Resources\Nasabahs\Pages;

use App\Filament\Resources\Nasabahs\NasabahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNasabahs extends ListRecords
{
    protected static string $resource = NasabahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
