<?php

namespace App\Filament\Resources\TitikBioporis\Pages;

use App\Filament\Resources\TitikBioporis\TitikBioporiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTitikBioporis extends ListRecords
{
    protected static string $resource = TitikBioporiResource::class;
    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\OrganikStats::class];
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
