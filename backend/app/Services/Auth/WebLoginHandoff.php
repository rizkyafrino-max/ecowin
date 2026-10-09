<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Serah-terima login dari halaman Laravel ke aplikasi web Nasabah (SPA).
 *
 * Alur seperti Authorization Code + PKCE: SPA membuat `code_verifier`, mengirim
 * `code_challenge` (SHA-256) saat memulai login; setelah Google & verifikasi server,
 * Laravel menerbitkan kode acak sekali pakai (60 dtk) yang hanya bisa ditukar token
 * oleh pemilik verifier. Kode yang bocor lewat URL/riwayat tidak berguna tanpa verifier.
 */
class WebLoginHandoff
{
    public static function validChallenge(mixed $challenge): bool
    {
        return is_string($challenge) && preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge) === 1;
    }

    public function issue(User $user, string $challenge): string
    {
        $code = Str::random(64);

        Cache::put($this->key($code), ['user' => $user->id, 'challenge' => $challenge], config('ecowin.web_login_code_ttl', 60));

        return $code;
    }

    /**
     * Tukar kode (sekali pakai) + verifier. Null bila kode salah/kedaluwarsa/terpakai atau verifier tidak cocok.
     */
    public function redeem(string $code, string $verifier): ?User
    {
        if (preg_match('/^[A-Za-z0-9]{64}$/', $code) !== 1 || preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier) !== 1) {
            return null;
        }

        $data = Cache::pull($this->key($code));

        if (! is_array($data)) {
            return null;
        }

        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        if (! hash_equals($data['challenge'], $expected)) {
            return null;
        }

        return User::query()->find($data['user']);
    }

    public function redirectUrl(string $code): string
    {
        return config('ecowin.web_url').'/auth/callback?'.http_build_query(['code' => $code]);
    }

    private function key(string $code): string
    {
        return 'web-login-code:'.hash('sha256', $code);
    }
}
