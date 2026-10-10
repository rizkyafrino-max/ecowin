<?php

namespace Tests;

use App\Models\User;
use App\Services\Auth\ApiTokenIssuer;
use App\Services\Auth\GoogleIdentity;
use App\Services\Auth\GoogleTokenVerifier;
use App\Services\Auth\InvalidGoogleTokenException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ganti verifier Google dengan fake: token "valid:<email>:<sub>[:unverified]" dianggap sah.
     */
    protected function fakeGoogle(): void
    {
        config(['services.google.client_id' => 'web-client.apps.googleusercontent.com', 'services.google.client_secret' => 'secret', 'services.google.allowed_audiences' => ['web-client.apps.googleusercontent.com']]);

        $this->app->instance(GoogleTokenVerifier::class, new class implements GoogleTokenVerifier
        {
            public function verify(string $idToken, array $allowedAudiences): GoogleIdentity
            {
                $parts = explode(':', str_replace('valid:', '', $idToken));

                if (! str_starts_with($idToken, 'valid:')) {
                    throw new InvalidGoogleTokenException('Tanda tangan token tidak valid.');
                }

                return new GoogleIdentity(
                    googleId: $parts[1] ?? 'sub-'.$parts[0],
                    email: $parts[0],
                    emailVerified: ($parts[2] ?? '') !== 'unverified',
                    name: 'Test',
                    nonce: $parts[3] ?? null,
                );
            }
        });
    }

    /**
     * Token Google palsu dengan panjang minimal yang lolos validasi request.
     */
    protected function googleToken(string $email, string $sub = 'sub-1', bool $verified = true, ?string $nonce = null): string
    {
        return 'valid:'.$email.':'.$sub.':'.($verified ? 'verified' : 'unverified').($nonce ? ':'.$nonce : '').':'.str_repeat('x', 120);
    }

    protected function actingAsApi(User $user): static
    {
        Sanctum::actingAs($user, [ApiTokenIssuer::ABILITY_ACCESS]);

        return $this;
    }
}
