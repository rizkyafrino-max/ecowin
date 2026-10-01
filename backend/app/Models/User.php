<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'bank_sampah_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Hanya admin dan petugas yang boleh masuk panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'petugas']);
    }

    // Filament mencari kolom "name"; di tabel kita namanya "nama"
    public function getFilamentName(): string
    {
        return $this->nama;
    }

    // Helper: apakah user ini admin?
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Helper: apakah user ini petugas?
    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    // Relasi ke bank sampah (petugas memiliki satu bank sampah)
    public function bankSampah()
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function nasabahDibuat()
    {
        return $this->hasMany(Nasabah::class, 'dibuat_oleh');
    }
}
