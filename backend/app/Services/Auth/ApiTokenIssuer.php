<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Access token (pendek) + refresh token (panjang, sekali pakai/dirotasi).
 */
class ApiTokenIssuer
{
    public const ABILITY_ACCESS = 'access';

    public const ABILITY_REFRESH = 'refresh';

    /**
     * @return array{access_token: string, access_expires_at: string, refresh_token: string, refresh_expires_at: string, token_type: string}
     */
    public function issue(User $user, string $deviceName): array
    {
        $deviceName = mb_substr(trim($deviceName) ?: 'android', 0, 100);
        $accessExpiresAt = now()->addMinutes(config('ecowin.tokens.access_ttl'));
        $refreshExpiresAt = now()->addMinutes(config('ecowin.tokens.refresh_ttl'));

        $access = $user->createToken($deviceName, [self::ABILITY_ACCESS], $accessExpiresAt);
        $refresh = $user->createToken($deviceName.':refresh', [self::ABILITY_REFRESH], $refreshExpiresAt);

        return [
            'token_type' => 'Bearer',
            'access_token' => $access->plainTextToken,
            'access_expires_at' => $accessExpiresAt->toIso8601String(),
            'refresh_token' => $refresh->plainTextToken,
            'refresh_expires_at' => $refreshExpiresAt->toIso8601String(),
        ];
    }

    /**
     * Rotasi: refresh token lama dicabut, token akses lama untuk device yang sama dicabut.
     *
     * @return array{access_token: string, access_expires_at: string, refresh_token: string, refresh_expires_at: string, token_type: string}
     */
    public function refresh(PersonalAccessToken $refreshToken): array
    {
        /** @var User $user */
        $user = $refreshToken->tokenable;
        $deviceName = str($refreshToken->name)->beforeLast(':refresh')->toString();

        $user->tokens()->where('name', $deviceName)->delete();
        $refreshToken->delete();

        return $this->issue($user, $deviceName);
    }

    public function revokeDevice(PersonalAccessToken $token): void
    {
        $deviceName = str($token->name)->beforeLast(':refresh')->toString();

        $token->tokenable->tokens()->whereIn('name', [$deviceName, $deviceName.':refresh'])->delete();
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
