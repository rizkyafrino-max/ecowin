<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Auth\GoogleAuthenticator;
use App\Services\Auth\GoogleOAuthClient;
use App\Services\Auth\GoogleTokenVerifier;
use App\Services\Auth\InvalidGoogleTokenException;
use App\Services\Auth\WebLoginHandoff;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * SATU pintu login Google untuk semua role (Authorization Code + PKCE + state + nonce).
 *  - Admin/Petugas: sesi web Laravel (persisten) -> dashboard Filament.
 *  - Nasabah: tidak punya sesi web; diserahkan ke aplikasi web Nasabah dengan kode sekali pakai (PKCE).
 *  - intent=register: identitas Google terverifikasi disimpan di sesi server, lalu lanjut ke form pendaftaran.
 */
class GoogleWebAuthController extends Controller
{
    public function __construct(
        private GoogleOAuthClient $oauth,
        private GoogleTokenVerifier $verifier,
        private GoogleAuthenticator $authenticator,
        private WebLoginHandoff $handoff,
        private AuditLogger $audit,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (Auth::check() && $request->query('app') !== 'web') {
            return redirect()->to(Filament::getDefaultPanel()->getUrl());
        }

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->gagal('Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di .env.');
        }

        $auth = $this->oauth->authorizationRequest();

        $request->session()->put('google_oauth', [
            'state' => $auth['state'],
            'nonce' => $auth['nonce'],
            'verifier' => $auth['verifier'],
        ]);

        // Niat login: dari aplikasi web Nasabah (membawa PKCE challenge) dan/atau pendaftaran.
        $challenge = $request->query('c');
        $request->session()->put('login_intent', [
            'challenge' => WebLoginHandoff::validChallenge($challenge) ? $challenge : null,
            'register' => $request->query('intent') === 'register',
        ]);

        return redirect()->away($auth['url']);
    }

    public function callback(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull('google_oauth');
        $intent = $request->session()->pull('login_intent', ['challenge' => null, 'register' => false]);

        if ($request->filled('error')) {
            return $this->gagal('Login Google dibatalkan.');
        }

        if (! is_array($pending) || ! is_string($request->query('state')) || ! hash_equals($pending['state'], $request->query('state')) || ! is_string($request->query('code'))) {
            return $this->gagal('Sesi login tidak valid atau kedaluwarsa. Silakan coba lagi.');
        }

        try {
            $idToken = $this->oauth->exchangeCodeForIdToken($request->query('code'), $pending['verifier']);
            $identity = $this->verifier->verify($idToken, [config('services.google.client_id')]);

            if ($identity->nonce === null || ! hash_equals($pending['nonce'], $identity->nonce)) {
                throw new InvalidGoogleTokenException('Nonce tidak cocok.');
            }
        } catch (InvalidGoogleTokenException $e) {
            $this->audit->log('login_gagal', detail: 'web: '.$e->getMessage());

            return $this->gagal('Verifikasi akun Google gagal.');
        }

        if ($intent['register']) {
            return $this->mulaiPendaftaran($request, $identity, $intent);
        }

        try {
            $user = $this->authenticator->resolveUser($identity, [Role::Admin->value, Role::Petugas->value, Role::Nasabah->value]);
        } catch (ValidationException $e) {
            return $this->gagal(collect($e->errors())->flatten()->first() ?? 'Akses ditolak.');
        }

        if ($user->isNasabah()) {
            return $this->serahkanKeWeb($user, $intent['challenge']);
        }

        // Persistent login: remember me aktif, sesi diregenerasi (anti session fixation).
        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getDefaultPanel()->getUrl());
    }

    private function mulaiPendaftaran(Request $request, $identity, array $intent): RedirectResponse
    {
        if (! $identity->emailVerified) {
            return $this->gagal('Email Google belum terverifikasi.');
        }

        if (User::query()->where('email', $identity->email)->orWhere('google_id', $identity->googleId)->exists()) {
            return $this->gagal('Akun Google ini sudah terdaftar. Silakan masuk.');
        }

        // Identitas disimpan di SESI SERVER (bukan di form) agar email/Google ID tidak bisa dipalsukan.
        $request->session()->put('pending_registration', [
            'identity' => ['google_id' => $identity->googleId, 'email' => $identity->email, 'name' => $identity->name, 'avatar' => $identity->avatar],
            'challenge' => $intent['challenge'],
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        return redirect()->route('daftar.lengkapi');
    }

    public function serahkanKeWeb(User $user, ?string $challenge): RedirectResponse
    {
        // Tanpa PKCE challenge (login dibuka langsung di halaman Laravel): arahkan ke web Nasabah,
        // yang akan memulai login lagi dengan challenge miliknya.
        if ($challenge === null) {
            return redirect()->away(config('ecowin.web_url').'/masuk');
        }

        $this->audit->log('login', $user, actor: $user, detail: 'web-nasabah');

        return redirect()->away($this->handoff->redirectUrl($this->handoff->issue($user, $challenge)));
    }

    private function gagal(string $pesan): RedirectResponse
    {
        return redirect()->route('filament.admin.auth.login')->with('auth_error', $pesan);
    }
}
