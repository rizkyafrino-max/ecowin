<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Pages;

use App\Filament\Resources\TransaksiAnorganiks\TransaksiAnorganikResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransaksiAnorganik extends EditRecord
{
    protected static string $resource = TransaksiAnorganikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
