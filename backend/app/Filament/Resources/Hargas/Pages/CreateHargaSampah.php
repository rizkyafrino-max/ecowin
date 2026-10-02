<?php

namespace App\Filament\Resources\Hargas\Pages;

use App\Filament\Resources\Hargas\HargaSampahResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHargaSampah extends CreateRecord
{
    protected static string $resource = HargaSampahResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dibuat_oleh'] = auth()->id();

        return $data;
    }
}
