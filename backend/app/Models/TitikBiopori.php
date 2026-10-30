<?php

namespace App\Models;

use App\Models\Concerns\VisibleToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lokasi lubang Biopori (pipa BioporiPrint dari plastik daur ulang hasil 3D print).
 */
class TitikBiopori extends Model
{
    use HasFactory, VisibleToUser;

    public const PANEN_BELUM = 'belum_panen';

    public const PANEN_SIAP = 'siap_panen';

    public const PANEN_SUDAH = 'sudah_panen';

    public const STATUS_PANEN = [
        self::PANEN_BELUM => 'Belum panen',
        self::PANEN_SIAP => 'Siap panen',
        self::PANEN_SUDAH => 'Sudah panen',
    ];

    public const VERIFIKASI_PENDING = 'pending';

    public const VERIFIKASI_APPROVED = 'approved';

    public const VERIFIKASI_REJECTED = 'rejected';

    protected $table = 'titik_biopori';

    /** Hanya titik yang sudah disetujui yang tampil di peta resmi dan boleh dipakai untuk melapor. */
    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('verifikasi'), self::VERIFIKASI_APPROVED);
    }

    public function isPendingVerifikasi(): bool
    {
        return $this->verifikasi === self::VERIFIKASI_PENDING;
    }

    protected $fillable = [
        'nasabah_id',
        'nama_lokasi',
        'alamat_rt_rw',
        'deskripsi_lokasi',
        'latitude',
        'longitude',
        'tanggal_tanam',
        'jumlah_pipa',
        'bioporiprint',
        'status',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tanam' => 'date',
            'bioporiprint' => 'boolean',
            'terakhir_diisi_at' => 'datetime',
            'estimasi_panen_at' => 'datetime',
            'dipanen_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
            'hasil_kompos_kg' => 'decimal:2',
        ];
    }

    /**
     * Status panen efektif: "siap panen" otomatis bila estimasi panen sudah lewat.
     */
    public function statusPanenEfektif(): string
    {
        if ($this->status_panen === self::PANEN_SUDAH) {
            return self::PANEN_SUDAH;
        }

        if ($this->estimasi_panen_at && $this->estimasi_panen_at->isPast()) {
            return self::PANEN_SIAP;
        }

        return self::PANEN_BELUM;
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id')->withTrashed();
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh')->withTrashed();
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh')->withTrashed();
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasBiopori::class, 'titik_biopori_id');
    }
}
