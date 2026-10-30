<?php

namespace App\Filament\Resources\JadwalPengumpulans\Pages;

use App\Filament\Resources\JadwalPengumpulans\JadwalPengumpulanResource;
use App\Models\JadwalPengumpulan;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJadwalPengumpulan extends EditRecord
{
    protected static string $resource = JadwalPengumpulanResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var JadwalPengumpulan $record */
        $sebelum = $record->attributesToArray();
        $record->fill($data);
        $record->forceFill(['hari' => array_values(array_map('intval', $data['hari'] ?? []))])->save();
        app(AuditLogger::class)->log('ubah_jadwal_pengumpulan', $record, $sebelum, $record->attributesToArray());

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
