<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
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

        $jkSebelum = $record->jenis_kelamin;
        $jkBaru = in_array($data['jenis_kelamin'] ?? null, [User::JK_LAKI, User::JK_PEREMPUAN], true) ? $data['jenis_kelamin'] : null;

        $record->forceFill([
            'nama' => $data['nama'],
            'email' => mb_strtolower(trim($data['email'])),
            'role' => $data['role'],
            'status' => $data['status'],
            'jenis_kelamin' => $jkBaru,
            'bank_sampah_id' => $data['role'] === Role::Petugas->value ? ($data['bank_sampah_id'] ?? null) : null,
        ])->save();

        if ($jkSebelum !== $jkBaru) {
            app(AuditLogger::class)->log('ubah_jenis_kelamin', $record, ['jenis_kelamin' => $jkSebelum], ['jenis_kelamin' => $jkBaru]);
        }

        return $record;
    }
}
