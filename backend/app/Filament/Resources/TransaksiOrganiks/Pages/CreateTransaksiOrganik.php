<?php

namespace App\Filament\Resources\TransaksiOrganiks\Pages;

use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use App\Services\TransaksiService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CreateTransaksiOrganik extends CreateRecord
{
    protected static string $resource = TransaksiOrganikResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(! empty($data['checklist_bebas_plastik']) && ! empty($data['checklist_bebas_logam']), 422, 'Sampah organik wajib bebas plastik dan logam.');

        try {
            return app(TransaksiService::class)->catatOrganik(
                auth()->user(),
                TransaksiOrganikResource::findManageableNasabah($data['nasabah_id']),
                Arr::only($data, ['jenis_organik', 'berat_kg', 'metode_pengolahan', 'lokasi', 'tanggal']),
            );
        } catch (ValidationException $e) {
            // Alasan penolakan dari layanan (mis. nasabah belum diverifikasi) harus terlihat, bukan formulir yang diam saja.
            Notification::make()->danger()->title('Transaksi belum bisa dicatat')->body((string) collect($e->errors())->flatten()->first())->send();
            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
