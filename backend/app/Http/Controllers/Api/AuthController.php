<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\RegisterNasabahRequest;
use App\Models\BankSampah;
use App\Services\NasabahService;
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
        private NasabahService $nasabah,
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
     * POST /api/auth/register — daftar Nasabah baru dengan Google ID token + data diri.
     * Akun berstatus pending sampai diverifikasi Petugas; token langsung diberikan agar bisa masuk.
     */
    public function register(RegisterNasabahRequest $request): JsonResponse
    {
        try {
            $identity = $this->verifier->verify($request->string('id_token'), config('services.google.allowed_audiences'));
        } catch (InvalidGoogleTokenException $e) {
            $this->audit->log('daftar_gagal', detail: 'api: '.$e->getMessage());

            return response()->json(['message' => 'Token Google tidak valid.', 'errors' => ['id_token' => [$e->getMessage()]]], 401);
        }

        $nasabah = $this->nasabah->daftarMandiri($identity, $request->safe()->only(['nama', 'no_hp', 'alamat_rt_rw', 'bank_sampah_id']));
        $user = $nasabah->user;
        $pair = $this->tokens->issue($user, (string) $request->input('device_name', 'android'));

        $this->audit->log('daftar_mandiri', $nasabah, null, ['email' => $user->email, 'bank_sampah_id' => $nasabah->bank_sampah_id, 'via' => 'api'], $user);

        return response()->json([
            ...$pair,
            'user' => new UserResource($user->load('nasabah.bankSampah')),
        ], 201);
    }

    /**
     * GET /api/bank-sampah/publik — daftar Bank Sampah aktif untuk form pendaftaran (tanpa data sensitif).
     */
    public function bankSampahPublik(): JsonResponse
    {
        return response()->json([
            'data' => BankSampah::query()->where('status', 'aktif')->orderBy('nama_bank_sampah')->get(['id', 'nama_bank_sampah', 'rt', 'rw'])
                ->map(fn (BankSampah $b) => ['id' => $b->id, 'nama' => $b->nama_bank_sampah, 'rt' => $b->rt, 'rw' => $b->rw]),
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
