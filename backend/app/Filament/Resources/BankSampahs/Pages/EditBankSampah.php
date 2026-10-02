<?php

namespace App\Filament\Resources\BankSampahs\Pages;

use App\Filament\Resources\BankSampahs\BankSampahResource;
use App\Models\BankSampah;
use Filament\Resources\Pages\EditRecord;

class EditBankSampah extends EditRecord
{
    protected static string $resource = BankSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

}
