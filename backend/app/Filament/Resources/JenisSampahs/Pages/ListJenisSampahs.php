<?php

namespace App\Filament\Resources\JenisSampahs\Pages;

use App\Filament\Resources\JenisSampahs\JenisSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisSampahs extends ListRecords
{
    protected static string $resource = JenisSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
