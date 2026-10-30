<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi dalam aplikasi. Hanya dibuat oleh sistem lewat NotifikasiService (tidak mass-assignable dari request).
 */
class Notifikasi extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'notifikasi';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['dibaca_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
