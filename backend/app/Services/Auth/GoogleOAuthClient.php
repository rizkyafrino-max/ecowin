<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * OAuth 2.0 Authorization Code + PKCE untuk login Google di dashboard web.
 */
class GoogleOAuthClient
{
    /**
     * @return array{url: string, state: string, nonce: string, verifier: string}
     */
    public function authorizationRequest(): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);

        return [
            'url' => config('services.google.auth_url').'?'.$query,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
        ];
    }

    /**
     * Tukar authorization code menjadi ID token Google.
     */
    public function exchangeCodeForIdToken(string $code, string $verifier): string
    {
        try {
            $response = Http::asForm()->timeout(10)->post(config('services.google.token_url'), [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
                'code_verifier' => $verifier,
            ]);
        } catch (Throwable) {
            throw new InvalidGoogleTokenException('Tidak dapat menghubungi server Google.');
        }

        $idToken = $response->json('id_token');

        if (! $response->successful() || ! is_string($idToken)) {
            throw new InvalidGoogleTokenException('Gagal menukar kode otorisasi Google.');
        }

        return $idToken;
    }
}
