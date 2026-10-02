<?php

namespace App\Filament\Resources\Nasabahs\Pages;

use App\Filament\Resources\Nasabahs\NasabahResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNasabah extends CreateRecord
{
    protected static string $resource = NasabahResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->isPetugas()) {
            $data['bank_sampah_id'] = auth()->user()->bank_sampah_id;
        }

        $data['dibuat_oleh'] = auth()->id();

        return $data;
    }
}
