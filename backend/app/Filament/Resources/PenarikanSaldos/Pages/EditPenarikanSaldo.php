<?php

namespace App\Filament\Resources\PenarikanSaldos\Pages;

use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenarikanSaldo extends EditRecord
{
    protected static string $resource = PenarikanSaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
