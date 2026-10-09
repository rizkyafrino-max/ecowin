<?php

namespace App\Filament\Resources\HargaSampahs\Pages;

use App\Filament\Resources\HargaSampahs\HargaSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHargaSampahs extends ListRecords
{
    protected static string $resource = HargaSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
