<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifikasi Google ID token secara lokal menggunakan sertifikat publik Google
 * (https://www.googleapis.com/oauth2/v1/certs), tanpa mempercayai data apa pun
 * yang dikirim klien selain token itu sendiri.
 */
class GoogleIdTokenVerifier implements GoogleTokenVerifier
{
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    private const CLOCK_SKEW_SECONDS = 60;

    public function verify(string $idToken, array $allowedAudiences): GoogleIdentity
    {
        $allowedAudiences = array_values(array_filter($allowedAudiences));

        if ($allowedAudiences === []) {
            throw new InvalidGoogleTokenException('Google client ID belum dikonfigurasi di server.');
        }

        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new InvalidGoogleTokenException('Format token tidak valid.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = $this->decodeJson($encodedHeader);
        $payload = $this->decodeJson($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (($header['alg'] ?? null) !== 'RS256' || empty($header['kid'])) {
            throw new InvalidGoogleTokenException('Algoritma token tidak didukung.');
        }

        $certificate = $this->certificates()[$header['kid']] ?? null;

        if ($certificate === null) {
            // Sertifikat Google berotasi; muat ulang sekali sebelum menolak.
            $certificate = $this->certificates(refresh: true)[$header['kid']] ?? null;
        }

        if ($certificate === null) {
            throw new InvalidGoogleTokenException('Kunci penandatangan token tidak dikenal.');
        }

        $publicKey = openssl_pkey_get_public($certificate);

        if ($publicKey === false || openssl_verify($encodedHeader.'.'.$encodedPayload, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new InvalidGoogleTokenException('Tanda tangan token tidak valid.');
        }

        $now = time();

        if (! in_array($payload['iss'] ?? null, self::ISSUERS, true)) {
            throw new InvalidGoogleTokenException('Penerbit token tidak valid.');
        }

        if (! in_array($payload['aud'] ?? null, $allowedAudiences, true)) {
            throw new InvalidGoogleTokenException('Token bukan untuk aplikasi ini (audience tidak cocok).');
        }

        if (! isset($payload['exp']) || (int) $payload['exp'] < $now - self::CLOCK_SKEW_SECONDS) {
            throw new InvalidGoogleTokenException('Token sudah kedaluwarsa.');
        }

        if (isset($payload['iat']) && (int) $payload['iat'] > $now + self::CLOCK_SKEW_SECONDS) {
            throw new InvalidGoogleTokenException('Token belum berlaku.');
        }

        if (empty($payload['sub']) || empty($payload['email'])) {
            throw new InvalidGoogleTokenException('Token tidak memuat identitas akun.');
        }

        return new GoogleIdentity(
            googleId: (string) $payload['sub'],
            email: mb_strtolower((string) $payload['email']),
            emailVerified: ($payload['email_verified'] ?? false) === true || ($payload['email_verified'] ?? null) === 'true',
            name: $payload['name'] ?? null,
            avatar: $payload['picture'] ?? null,
            nonce: $payload['nonce'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    private function certificates(bool $refresh = false): array
    {
        $cacheKey = 'google-oauth-certs';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->get(config('services.google.certs_url'));
        } catch (Throwable) {
            throw new InvalidGoogleTokenException('Tidak dapat menghubungi server Google.');
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new InvalidGoogleTokenException('Gagal mengambil sertifikat Google.');
        }

        $ttl = 3600;

        if (preg_match('/max-age=(\d+)/', (string) $response->header('Cache-Control'), $matches)) {
            $ttl = max(60, (int) $matches[1]);
        }

        Cache::put($cacheKey, $response->json(), $ttl);

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $segment): array
    {
        $decoded = json_decode($this->base64UrlDecode($segment), true);

        if (! is_array($decoded)) {
            throw new InvalidGoogleTokenException('Isi token tidak valid.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new InvalidGoogleTokenException('Encoding token tidak valid.');
        }

        return $decoded;
    }
}
