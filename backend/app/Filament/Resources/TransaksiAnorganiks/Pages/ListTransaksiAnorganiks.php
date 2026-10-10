<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Pages;

use App\Filament\Resources\TransaksiAnorganiks\TransaksiAnorganikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiAnorganiks extends ListRecords
{
    protected static string $resource = TransaksiAnorganikResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Catat setoran')];
    }
}
