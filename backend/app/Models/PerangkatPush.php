<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Token FCM sebuah perangkat. Tidak mass-assignable dari request; token terenkripsi saat disimpan. */
class PerangkatPush extends Model
{
    protected $table = 'perangkat_push';

    protected $guarded = ['*'];

    protected $hidden = ['token', 'token_hash'];

    protected function casts(): array
    {
        return ['token' => 'encrypted', 'terakhir_aktif_at' => 'datetime'];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
