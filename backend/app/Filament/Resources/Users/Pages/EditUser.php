<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        DB::transaction(function (): void {
            \App\Models\BankSampah::query()->lockForUpdate()->findOrFail($this->record->bank_sampah_id);
            abort_if(User::query()->where('role', 'petugas')->where('bank_sampah_id', $this->record->bank_sampah_id)->whereKeyNot($this->record->id)->exists(), 422, 'Bank sampah sudah ditugaskan kepada petugas lain.');
        });
    }
}
