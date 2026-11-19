<?php

namespace App\Filament\Resources\TransaksiAnorganiks\Pages;

use App\Filament\Resources\TransaksiAnorganiks\TransaksiAnorganikResource;
use App\Models\JenisSampah;
use App\Services\TransaksiService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CreateTransaksiAnorganik extends CreateRecord
{
    protected static string $resource = TransaksiAnorganikResource::class;

    /**
     * Nasabah divalidasi ulang terhadap scope petugas; harga & total dihitung di server.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $foto = $data['foto_dokumentasi'] ?? null;

        try {
            return app(TransaksiService::class)->catatAnorganik(
                auth()->user(),
                TransaksiAnorganikResource::findManageableNasabah($data['nasabah_id']),
                JenisSampah::query()->findOrFail($data['jenis_sampah_id']),
                (float) $data['berat_kg'],
                null,
                $foto instanceof UploadedFile ? $foto : null,
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
