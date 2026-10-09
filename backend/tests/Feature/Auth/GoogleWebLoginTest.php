<?php

namespace Tests\Feature\Auth;

use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleWebLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGoogle();
    }

    public function test_login_page_only_offers_google(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('EcoWin')->assertSee('Masuk')
            ->assertSee('Bank Sampah Digital Berkelanjutan')
            ->assertSee('Continue with Google')
            ->assertSee('Gunakan akun Google yang telah terverifikasi untuk mengakses EcoWin.')
            ->assertDontSee('type="password"', false);
    }

    public function test_redirect_uses_pkce_state_and_nonce(): void
    {
        $response = $this->get('/auth/google/redirect')->assertRedirect();
        $url = $response->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('google_oauth.state'), $query['state']);
        $this->assertSame(session('google_oauth.nonce'), $query['nonce']);
    }

    public function test_callback_with_wrong_state_is_rejected(): void
    {
        $this->withSession(['google_oauth' => ['state' => 'benar', 'nonce' => 'n', 'verifier' => 'v']])
            ->get('/auth/google/callback?state=salah&code=abc')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_admin_logs_in_with_persistent_remember_cookie(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin.ecowin@gmail.com']);
        $this->fakeTokenEndpoint($admin->email, 'nonce-1');

        $response = $this->withSession(['google_oauth' => ['state' => 'st', 'nonce' => 'nonce-1', 'verifier' => 'v']])
            ->get('/auth/google/callback?state=st&code=abc')
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->remember_token);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertDatabaseHas('audit_log', ['aksi' => 'login', 'user_id' => $admin->id, 'detail' => 'web']);
    }

    public function test_nonce_mismatch_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeTokenEndpoint($admin->email, 'nonce-lain');

        $this->withSession(['google_oauth' => ['state' => 'st', 'nonce' => 'nonce-1', 'verifier' => 'v']])
            ->get('/auth/google/callback?state=st&code=abc')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_nasabah_never_gets_a_dashboard_session(): void
    {
        $nasabah = Nasabah::factory()->create();
        $this->fakeTokenEndpoint($nasabah->user->email, 'n1');

        // Nasabah tidak memakai dashboard Filament: diarahkan ke aplikasi web Nasabah, tanpa sesi Laravel.
        $this->withSession(['google_oauth' => ['state' => 'st', 'nonce' => 'n1', 'verifier' => 'v']])
            ->get('/auth/google/callback?state=st&code=abc')
            ->assertRedirect(config('ecowin.web_url').'/masuk');

        $this->assertGuest();
        $this->get('/admin')->assertRedirect();
    }

    public function test_authenticated_user_skips_login_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/login')->assertRedirect();
    }

    public function test_disabled_user_session_is_terminated(): void
    {
        $petugas = User::factory()->petugas()->create();
        $this->actingAs($petugas);
        $petugas->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        $this->get('/admin')->assertRedirect();
        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
    }

    private function fakeTokenEndpoint(string $email, string $nonce): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['id_token' => $this->googleToken($email, 'sub-web', true, $nonce)])]);
    }
}
