<?php

namespace App\Filament\Resources\PenarikanSaldos\Pages;

use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use App\Services\PenarikanService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePenarikanSaldo extends CreateRecord
{
    protected static string $resource = PenarikanSaldoResource::class;

    /**
     * Status, bank_sampah_id, dan pemroses TIDAK bisa diisi dari form.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(PenarikanService::class)->ajukan(
            auth()->user(),
            PenarikanSaldoResource::findManageableNasabah($data['nasabah_id']),
            (int) $data['jumlah'],
            $data['catatan'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
