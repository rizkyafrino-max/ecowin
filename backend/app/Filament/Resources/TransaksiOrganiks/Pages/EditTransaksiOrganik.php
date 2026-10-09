<?php

namespace App\Filament\Resources\TransaksiOrganiks\Pages;

use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransaksiOrganik extends EditRecord
{
    protected static string $resource = TransaksiOrganikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
