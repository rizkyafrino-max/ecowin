<?php

namespace App\Filament\Resources\AktivitasBioporis\Pages;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use Filament\Resources\Pages\EditRecord;

class EditAktivitasBiopori extends EditRecord
{
    protected static string $resource = AktivitasBioporiResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status !== 'menunggu') {
            abort(403);
        }

        if (! in_array($data['status'] ?? null, ['disetujui', 'ditolak'], true)) {
            abort(422, 'Pilih keputusan verifikasi.');
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'diperiksa_oleh' => auth()->id(),
            'waktu_diperiksa' => now(),
        ]);
    }
}
