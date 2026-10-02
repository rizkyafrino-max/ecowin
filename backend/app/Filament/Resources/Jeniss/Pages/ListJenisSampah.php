<?php

namespace App\Filament\Resources\Jeniss\Pages;

use App\Filament\Resources\Jeniss\JenisSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisSampah extends ListRecords
{
    protected static string $resource = JenisSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
