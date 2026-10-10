<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Role/status/bank diisi eksplisit (bukan mass assignment) setelah divalidasi.
     */
    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(in_array($data['role'] ?? null, [Role::Admin->value, Role::Petugas->value], true), 422);

        $user = new User;
        $user->forceFill([
            'nama' => $data['nama'],
            'email' => mb_strtolower(trim($data['email'])),
            'role' => $data['role'],
            'status' => $data['status'] ?? User::STATUS_AKTIF,
            'bank_sampah_id' => $data['role'] === Role::Petugas->value ? $data['bank_sampah_id'] : null,
        ])->save();

        return $user;
    }
}
