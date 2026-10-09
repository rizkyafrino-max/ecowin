<?php

namespace App\Filament\Resources\AktivitasBioporis\Pages;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAktivitasBiopori extends ViewRecord
{
    protected static string $resource = AktivitasBioporiResource::class;

    protected function getHeaderActions(): array
    {
        return AktivitasBioporiResource::decisionActions();
    }
}
