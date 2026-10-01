<?php

namespace App\Filament\Resources\AktivitasBioporis\Pages;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAktivitasBioporis extends ListRecords
{
    protected static string $resource = AktivitasBioporiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
