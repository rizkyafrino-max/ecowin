<?php

namespace App\Services\Auth;

interface GoogleTokenVerifier
{
    /**
     * Verifikasi Google ID token (JWT RS256): signature, issuer, audience,
     * expiry. Melempar InvalidGoogleTokenException bila tidak valid.
     *
     * @param  list<string>  $allowedAudiences
     */
    public function verify(string $idToken, array $allowedAudiences): GoogleIdentity;
}
