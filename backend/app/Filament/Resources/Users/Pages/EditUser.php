<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  User  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless(in_array($data['role'] ?? null, [Role::Admin->value, Role::Petugas->value], true), 422);

        // Admin tidak boleh menurunkan/menonaktifkan dirinya sendiri (mencegah terkunci).
        if ($record->is(auth()->user())) {
            $data['role'] = $record->role;
            $data['status'] = $record->status;
        }

        $record->forceFill([
            'nama' => $data['nama'],
            'email' => mb_strtolower(trim($data['email'])),
            'role' => $data['role'],
            'status' => $data['status'],
            'bank_sampah_id' => $data['role'] === Role::Petugas->value ? ($data['bank_sampah_id'] ?? null) : null,
        ])->save();

        return $record;
    }
}
