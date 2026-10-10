<?php

namespace App\Filament\Resources\TransaksiOrganiks\Pages;

use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use App\Services\TransaksiService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateTransaksiOrganik extends CreateRecord
{
    protected static string $resource = TransaksiOrganikResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(! empty($data['checklist_bebas_plastik']) && ! empty($data['checklist_bebas_logam']), 422, 'Sampah organik wajib bebas plastik dan logam.');

        return app(TransaksiService::class)->catatOrganik(
            auth()->user(),
            TransaksiOrganikResource::findManageableNasabah($data['nasabah_id']),
            Arr::only($data, ['jenis_organik', 'berat_kg', 'metode_pengolahan', 'lokasi', 'tanggal']),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
