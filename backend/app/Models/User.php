<?php

namespace App\Models;

use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun login (Google) untuk semua role.
 *
 * role, status, bank_sampah_id, google_id, email_verified_at sengaja TIDAK
 * mass-assignable agar tidak bisa dimanipulasi lewat request; hanya diisi
 * eksplisit oleh sistem/Admin.
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    protected $fillable = [
        'nama',
        'email',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $attributes = [
        'status' => self::STATUS_AKTIF,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Hanya admin & petugas aktif dengan email Google terverifikasi yang boleh masuk panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive()
            && ($this->isAdmin() || ($this->isPetugas() && $this->bank_sampah_id !== null));
    }

    public function getFilamentName(): string
    {
        return (string) $this->nama;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar;
    }

    public function roleEnum(): ?Role
    {
        return Role::tryFrom((string) $this->role);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin->value;
    }

    public function isPetugas(): bool
    {
        return $this->role === Role::Petugas->value;
    }

    public function isNasabah(): bool
    {
        return $this->role === Role::Nasabah->value;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isPetugas();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    /**
     * Petugas hanya boleh menangani data bank sampah miliknya; admin semua.
     */
    public function canManageBankSampah(?int $bankSampahId): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->isPetugas()
            && $this->bank_sampah_id !== null
            && $bankSampahId !== null
            && (int) $this->bank_sampah_id === (int) $bankSampahId;
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function nasabah(): HasOne
    {
        return $this->hasOne(Nasabah::class, 'user_id');
    }

    public function nasabahDibuat(): HasMany
    {
        return $this->hasMany(Nasabah::class, 'dibuat_oleh');
    }
}
