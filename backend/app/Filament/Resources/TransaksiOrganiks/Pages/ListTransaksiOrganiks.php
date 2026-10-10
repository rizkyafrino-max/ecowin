<?php

namespace App\Filament\Resources\TransaksiOrganiks\Pages;

use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiOrganiks extends ListRecords
{
    protected static string $resource = TransaksiOrganikResource::class;
    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\OrganikStats::class];
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Catat setoran organik')];
    }
}
