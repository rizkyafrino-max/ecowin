<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use App\Services\Auth\ApiTokenIssuer;
use App\Services\Auth\WebLoginHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/auth/exchange — tukar kode sekali pakai (dari login Google di halaman Laravel)
 * + PKCE verifier menjadi access/refresh token untuk aplikasi web Nasabah.
 */
class WebLoginController extends Controller
{
    public function __construct(private WebLoginHandoff $handoff, private ApiTokenIssuer $tokens, private AuditLogger $audit) {}

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:64'],
            'verifier' => ['required', 'string', 'min:43', 'max:128'],
        ]);

        $user = $this->handoff->redeem($data['code'], $data['verifier']);

        // Pesan sengaja seragam: tidak membedakan kode salah, kedaluwarsa, atau terpakai.
        if (! $user || ! $user->isActive() || $user->role !== Role::Nasabah->value || ! $user->nasabah?->isActive()) {
            $this->audit->log('login_gagal', detail: 'web-nasabah: kode tidak valid');

            return response()->json(['message' => 'Kode login tidak valid atau sudah kedaluwarsa. Silakan masuk lagi.'], 401);
        }

        $pair = $this->tokens->issue($user, 'web');

        return response()->json([
            ...$pair,
            'user' => new UserResource($user->load('nasabah.bankSampah')),
        ]);
    }
}
