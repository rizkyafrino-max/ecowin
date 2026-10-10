<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat audit log terpusat: user, aktivitas, model, model_id,
 * data sebelum/sesudah, IP, user agent, timestamp.
 */
class AuditLogger
{
    /**
     * Atribut yang tidak boleh pernah masuk audit log.
     */
    private const REDACTED = ['password', 'remember_token', 'google_id', 'pin', 'kartu_qr_token', 'nisn_atau_nik', 'foto_ktp_kk_path', 'token'];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(string $aksi, ?Model $model = null, ?array $before = null, ?array $after = null, ?User $actor = null, ?string $detail = null): AuditLog
    {
        $actor ??= Auth::user() instanceof User ? Auth::user() : null;
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        $log = new AuditLog;
        $log->forceFill([
            'user_id' => $actor?->id,
            'bank_sampah_id' => $model?->getAttribute('bank_sampah_id') ?? $actor?->bank_sampah_id,
            'aksi' => $aksi,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'data_sebelum' => $before !== null ? $this->clean($before) : null,
            'data_sesudah' => $after !== null ? $this->clean($after) : null,
            'detail' => $detail,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
        ])->save();

        return $log;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        return Arr::except($data, self::REDACTED);
    }
}
