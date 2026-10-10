<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Mencocokkan identitas Google terverifikasi dengan akun di database.
 * Role SELALU berasal dari database; akun tidak terdaftar ditolak.
 */
class GoogleAuthenticator
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<string>  $allowedRoles
     */
    public function resolveUser(GoogleIdentity $identity, array $allowedRoles): User
    {
        if (! $identity->emailVerified) {
            $this->fail($identity, 'Email Google belum terverifikasi.');
        }

        $user = User::query()->where('email', $identity->email)->first();

        if (! $user) {
            $this->fail($identity, 'Akun Google ini belum terdaftar di EcoWin. Hubungi petugas Bank Sampah Anda.');
        }

        // Akun yang sudah terhubung ke Google ID lain tidak boleh diambil alih.
        if ($user->google_id !== null && ! hash_equals((string) $user->google_id, $identity->googleId)) {
            $this->fail($identity, 'Akun ini terhubung dengan identitas Google yang berbeda.', $user);
        }

        if (! $user->isActive()) {
            $this->fail($identity, 'Akun Anda dinonaktifkan.', $user);
        }

        if (! in_array($user->role, $allowedRoles, true)) {
            $this->fail($identity, 'Peran akun ini tidak diizinkan masuk melalui aplikasi ini.', $user);
        }

        if ($user->isPetugas() && $user->bank_sampah_id === null) {
            $this->fail($identity, 'Akun petugas belum ditautkan ke Bank Sampah.', $user);
        }

        if ($user->isNasabah() && (! $user->nasabah || ! $user->nasabah->isActive())) {
            $this->fail($identity, 'Profil nasabah tidak ditemukan atau nonaktif.', $user);
        }

        $user->forceFill([
            'google_id' => $identity->googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'avatar' => $identity->avatar ?? $user->avatar,
            'last_login_at' => now(),
        ])->save();

        return $user;
    }

    private function fail(GoogleIdentity $identity, string $message, ?User $user = null): never
    {
        $this->audit->log('login_gagal', $user, after: ['email' => $identity->email, 'alasan' => $message], actor: $user);

        throw ValidationException::withMessages(['google' => [$message]]);
    }
}
