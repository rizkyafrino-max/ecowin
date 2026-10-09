<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use App\Services\Auth\ApiTokenIssuer;
use App\Services\Auth\GoogleAuthenticator;
use App\Services\Auth\GoogleTokenVerifier;
use App\Services\Auth\InvalidGoogleTokenException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private GoogleTokenVerifier $verifier,
        private GoogleAuthenticator $authenticator,
        private ApiTokenIssuer $tokens,
        private AuditLogger $audit,
    ) {}

    /**
     * POST /api/auth/google — tukar Google ID token menjadi access + refresh token.
     */
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        try {
            $identity = $this->verifier->verify($request->string('id_token'), config('services.google.allowed_audiences'));
        } catch (InvalidGoogleTokenException $e) {
            $this->audit->log('login_gagal', detail: 'api: '.$e->getMessage());

            return response()->json(['message' => 'Token Google tidak valid.', 'errors' => ['id_token' => [$e->getMessage()]]], 401);
        }

        $user = $this->authenticator->resolveUser($identity, [Role::Nasabah->value, Role::Petugas->value, Role::Admin->value]);
        $pair = $this->tokens->issue($user, (string) $request->input('device_name', 'android'));

        $this->audit->log('login', $user, actor: $user, detail: 'api');

        return response()->json([
            ...$pair,
            'user' => new UserResource($user->load('nasabah.bankSampah')),
        ]);
    }

    /**
     * POST /api/auth/refresh — Authorization: Bearer <refresh_token>. Token dirotasi.
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();

        return response()->json($this->tokens->refresh($token));
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('nasabah.bankSampah'));
    }

    /**
     * POST /api/auth/logout — cabut access & refresh token perangkat ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $this->tokens->revokeDevice($token);
        }

        $this->audit->log('logout', $user, actor: $user, detail: 'api');

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
