<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Harga dinamis & bertingkat: setiap baris berlaku untuk rentang berat
 * [minimal_berat, maksimal_berat] dalam periode [berlaku_mulai, berlaku_sampai].
 */
class HargaSampah extends Model
{
    use HasFactory;

    protected $table = 'harga_sampah';

    protected $fillable = [
        'jenis_sampah_id',
        'kondisi',
        'minimal_berat',
        'maksimal_berat',
        'harga_per_kg',
        'berlaku_mulai',
        'berlaku_sampai',
        'status',
    ];

    protected $attributes = [
        'kondisi' => 'Utuh',
        'minimal_berat' => 0,
        'status' => 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'datetime',
            'berlaku_sampai' => 'datetime',
            'minimal_berat' => 'decimal:2',
            'maksimal_berat' => 'decimal:2',
            'harga_per_kg' => 'integer',
        ];
    }

    public function scopeBerlaku(Builder $query, $pada = null): Builder
    {
        $pada ??= now();

        return $query->where('status', 'aktif')
            ->where('berlaku_mulai', '<=', $pada)
            ->where(fn (Builder $q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $pada));
    }

    public function scopeUntukBerat(Builder $query, float $berat): Builder
    {
        return $query->where('minimal_berat', '<=', $berat)
            ->where(fn (Builder $q) => $q->whereNull('maksimal_berat')->orWhere('maksimal_berat', '>=', $berat));
    }

    public function jenisSampah(): BelongsTo
    {
        return $this->belongsTo(JenisSampah::class, 'jenis_sampah_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function labelRentang(): string
    {
        $min = rtrim(rtrim(number_format((float) $this->minimal_berat, 2, ',', '.'), '0'), ',');
        $max = $this->maksimal_berat !== null ? rtrim(rtrim(number_format((float) $this->maksimal_berat, 2, ',', '.'), '0'), ',') : null;

        return $max !== null ? "{$min}–{$max} kg" : "≥ {$min} kg";
    }
}
