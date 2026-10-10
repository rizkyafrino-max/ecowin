<?php

namespace App\Filament\Resources\BankSampahs\Pages;

use App\Filament\Resources\BankSampahs\BankSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankSampahs extends ListRecords
{
    protected static string $resource = BankSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
