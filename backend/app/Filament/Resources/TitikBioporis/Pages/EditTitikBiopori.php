<?php

namespace App\Filament\Resources\TitikBioporis\Pages;

use App\Filament\Resources\TitikBioporis\TitikBioporiResource;
use App\Models\TitikBiopori;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditTitikBiopori extends EditRecord
{
    protected static string $resource = TitikBioporiResource::class;

    /**
     * @param  TitikBiopori  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! empty($data['nasabah_id'])) {
            $nasabah = TitikBioporiResource::findManageableNasabah($data['nasabah_id']);
            abort_unless((int) $nasabah->bank_sampah_id === (int) $record->bank_sampah_id, 403);
        }

        $record->fill(Arr::only($data, $record->getFillable()))->save();

        return $record;
    }
}
