<?php

namespace App\Filament\Resources\HargaSampahs\Pages;

use App\Filament\Resources\HargaSampahs\HargaSampahResource;
use App\Models\HargaSampah;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHargaSampah extends CreateRecord
{
    protected static string $resource = HargaSampahResource::class;

    /**
     * dibuat_oleh selalu dari user login, bukan input form.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $harga = new HargaSampah($data);
        $harga->forceFill(['dibuat_oleh' => auth()->id()])->save();

        return $harga;
    }
}
