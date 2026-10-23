<?php

namespace App\Filament\Resources\JadwalPengumpulans\Pages;

use App\Filament\Resources\JadwalPengumpulans\JadwalPengumpulanResource;
use App\Models\JadwalPengumpulan;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJadwalPengumpulan extends CreateRecord
{
    protected static string $resource = JadwalPengumpulanResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $j = new JadwalPengumpulan;
        $j->fill($data);
        $j->forceFill(['dibuat_oleh' => auth()->id(), 'hari' => array_values(array_map('intval', $data['hari'] ?? []))])->save();
        app(AuditLogger::class)->log('buat_jadwal_pengumpulan', $j, null, $j->attributesToArray());

        return $j;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
