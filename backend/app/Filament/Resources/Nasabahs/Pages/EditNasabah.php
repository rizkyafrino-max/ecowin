<?php

namespace App\Filament\Resources\Nasabahs\Pages;

use App\Filament\Resources\Nasabahs\NasabahResource;
use Filament\Resources\Pages\EditRecord;

class EditNasabah extends EditRecord
{
    protected static string $resource = NasabahResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['bank_sampah_id'] = $this->record->bank_sampah_id;
        $data['dibuat_oleh'] = $this->record->dibuat_oleh;
        unset($data['pin']);

        return $data;
    }
}
