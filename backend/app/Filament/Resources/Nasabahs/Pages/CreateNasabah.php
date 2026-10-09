<?php

namespace App\Filament\Resources\Nasabahs\Pages;

use App\Filament\Resources\Nasabahs\NasabahResource;
use App\Services\NasabahService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateNasabah extends CreateRecord
{
    protected static string $resource = NasabahResource::class;

    /**
     * Petugas: bank_sampah_id dipaksa milik petugas (input diabaikan oleh service).
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(NasabahService::class)->daftarkan(auth()->user(), $data, $data['foto_ktp_kk_path'] ?? null);
    }
}
