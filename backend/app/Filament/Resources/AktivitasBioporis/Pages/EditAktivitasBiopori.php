<?php

namespace App\Filament\Resources\AktivitasBioporis\Pages;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAktivitasBiopori extends EditRecord
{
    protected static string $resource = AktivitasBioporiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
