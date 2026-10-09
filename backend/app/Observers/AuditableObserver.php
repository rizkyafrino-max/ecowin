<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Mencatat create/update/delete data master (user, bank sampah, nasabah,
 * kategori, jenis, harga, titik biopori) ke audit log beserta data sebelum/sesudah.
 */
class AuditableObserver
{
    /**
     * Perubahan yang dicatat oleh service khusus atau tidak bermakna untuk audit.
     */
    private const IGNORED = ['updated_at', 'created_at', 'last_login_at', 'remember_token', 'avatar', 'google_id', 'email_verified_at', 'saldo'];

    public function __construct(private AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->audit->log('buat_'.$this->name($model), $model, null, $model->attributesToArray());
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::IGNORED);

        if ($changes === []) {
            return;
        }

        $before = Arr::only($model->getOriginal(), array_keys($changes));

        $this->audit->log('ubah_'.$this->name($model), $model, $before, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->audit->log('hapus_'.$this->name($model), $model, $model->attributesToArray(), null);
    }

    private function name(Model $model): string
    {
        return Str::snake(class_basename($model));
    }
}
