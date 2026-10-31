<?php

namespace App\Filament\Resources\Nasabahs\Pages;

use App\Enums\Role;
use App\Filament\Resources\Nasabahs\NasabahResource;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditNasabah extends EditRecord
{
    protected static string $resource = NasabahResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email'] = $this->record->user?->email;
        $data['jenis_kelamin'] = $this->record->user?->jenis_kelamin;

        return $data;
    }

    /**
     * @param  Nasabah  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return DB::transaction(function () use ($record, $data, $actor): Nasabah {
            $record->fill(Arr::only($data, ['nama', 'no_hp', 'alamat_rt_rw', 'nisn_atau_nik', 'foto_ktp_kk_path']));

            $extra = ['status' => $data['status'] ?? $record->status];

            if ($actor->isAdmin() && ! empty($data['bank_sampah_id'])) {
                $extra['bank_sampah_id'] = $data['bank_sampah_id'];
            }

            $record->forceFill($extra)->save();

            // Nasabah data lama (belum punya akun Google) otomatis ditautkan ke akun baru.
            $user = $record->user ?? new User;
            $jkSebelum = $user->jenis_kelamin;
            $jkBaru = in_array($data['jenis_kelamin'] ?? null, [User::JK_LAKI, User::JK_PEREMPUAN], true) ? $data['jenis_kelamin'] : null;
            $user->forceFill([
                'nama' => $record->nama,
                'email' => mb_strtolower(trim($data['email'])),
                'jenis_kelamin' => $jkBaru,
                'role' => Role::Nasabah->value,
                'bank_sampah_id' => $record->bank_sampah_id,
                'status' => $record->status === 'aktif' ? User::STATUS_AKTIF : User::STATUS_NONAKTIF,
            ])->save();

            if (! $record->user_id) {
                $record->forceFill(['user_id' => $user->id])->save();
            }

            if ($jkSebelum !== $jkBaru) {
                app(AuditLogger::class)->log('ubah_jenis_kelamin', $record, ['jenis_kelamin' => $jkSebelum], ['jenis_kelamin' => $jkBaru], $actor);
            }

            return $record;
        });
    }
}
