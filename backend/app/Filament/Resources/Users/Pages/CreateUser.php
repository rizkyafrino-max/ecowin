<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'petugas';

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function (): void {
            \App\Models\BankSampah::query()->lockForUpdate()->findOrFail($this->record->bank_sampah_id);
            abort_if(User::query()->where('role', 'petugas')->where('bank_sampah_id', $this->record->bank_sampah_id)->whereKeyNot($this->record->id)->exists(), 422, 'Bank sampah sudah ditugaskan kepada petugas lain.');
        });
    }
}
