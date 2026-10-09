<?php

namespace App\Filament\Resources\AktivitasBioporis\Pages;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use Filament\Resources\Pages\ListRecords;

class ListAktivitasBioporis extends ListRecords
{
    protected static string $resource = AktivitasBioporiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\OrganikStats::class];
    }
}
