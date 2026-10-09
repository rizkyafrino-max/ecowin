<?php

namespace App\Filament\Resources\TitikBioporis\Pages;

use App\Filament\Resources\TitikBioporis\TitikBioporiResource;
use App\Models\TitikBiopori;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateTitikBiopori extends CreateRecord
{
    protected static string $resource = TitikBioporiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        $bankId = $actor->bank_sampah_id;

        if (! empty($data['nasabah_id'])) {
            $bankId = TitikBioporiResource::findManageableNasabah($data['nasabah_id'])->bank_sampah_id;
        }

        abort_unless($bankId && $actor->canManageBankSampah($bankId), 403, 'Pilih nasabah penanggung jawab untuk menentukan Bank Sampah.');

        $titik = new TitikBiopori(Arr::only($data, (new TitikBiopori)->getFillable()));
        $titik->forceFill(['bank_sampah_id' => $bankId, 'dicatat_oleh' => $actor->id])->save();

        return $titik;
    }
}
