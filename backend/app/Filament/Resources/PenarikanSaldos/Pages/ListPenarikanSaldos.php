<?php

namespace App\Filament\Resources\PenarikanSaldos\Pages;

use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenarikanSaldos extends ListRecords
{
    protected static string $resource = PenarikanSaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajukan penarikan')];
    }
}
