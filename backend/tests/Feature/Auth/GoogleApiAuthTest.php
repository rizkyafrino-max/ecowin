<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGoogle();
    }

    public function test_nasabah_logs_in_with_google_and_gets_access_and_refresh_tokens(): void
    {
        $nasabah = Nasabah::factory()->create();

        $response = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email), 'device_name' => 'Pixel 8'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'access_expires_at', 'refresh_token', 'refresh_expires_at', 'user' => ['id', 'role', 'nasabah' => ['nomor_nasabah', 'saldo']]])
            ->assertJsonPath('user.role', 'nasabah')
            ->assertJsonMissingPath('user.nasabah.nisn_atau_nik')
            ->assertJsonMissingPath('user.nasabah.kartu_qr_token');

        // Sesi tetap aktif: token yang tersimpan langsung bisa dipakai (tanpa login ulang).
        $this->withToken($response->json('access_token'))->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.email', $nasabah->user->email);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'login', 'user_id' => $nasabah->user_id, 'detail' => 'api']);
        $this->assertSame('sub-1', $nasabah->user->fresh()->google_id);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email, verified: false)])
            ->assertStatus(422)->assertJsonValidationErrors('google');
    }

    public function test_unregistered_email_is_rejected_and_not_auto_created(): void
    {
        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken('orang.asing@gmail.com')])
            ->assertStatus(422)->assertJsonValidationErrors('google');

        $this->assertDatabaseMissing('users', ['email' => 'orang.asing@gmail.com']);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'login_gagal']);
    }

    public function test_invalid_google_token_returns_401(): void
    {
        $this->postJson('/api/auth/google', ['id_token' => str_repeat('a', 200)])->assertStatus(401);
    }

    public function test_role_comes_from_database_and_cannot_be_requested(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email), 'role' => 'admin', 'email' => 'admin@gmail.com'])
            ->assertOk()->assertJsonPath('user.role', 'nasabah');
    }

    public function test_different_roles_receive_their_own_role(): void
    {
        $petugas = User::factory()->petugas()->create();

        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($petugas->email)])->assertOk()->assertJsonPath('user.role', 'petugas');
    }

    public function test_disabled_account_cannot_login(): void
    {
        $nasabah = Nasabah::factory()->create();
        $nasabah->user->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->assertStatus(422);
    }

    public function test_account_bound_to_other_google_id_cannot_be_taken_over(): void
    {
        $nasabah = Nasabah::factory()->create();
        $nasabah->user->forceFill(['google_id' => 'asli-123'])->save();

        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email, sub: 'penyerang-999')])->assertStatus(422);
    }

    public function test_refresh_rotates_tokens_and_old_refresh_token_stops_working(): void
    {
        $nasabah = Nasabah::factory()->create();
        $login = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->json();

        $refreshed = $this->withToken($login['refresh_token'])->postJson('/api/auth/refresh')->assertOk()->json();
        $this->assertNotSame($login['access_token'], $refreshed['access_token']);

        $this->app['auth']->forgetGuards();
        $this->withToken($login['refresh_token'])->postJson('/api/auth/refresh')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($login['access_token'])->getJson('/api/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($refreshed['access_token'])->getJson('/api/auth/me')->assertOk();
    }

    public function test_refresh_token_cannot_be_used_as_access_token_and_vice_versa(): void
    {
        $nasabah = Nasabah::factory()->create();
        $login = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->json();

        $this->withToken($login['refresh_token'])->getJson('/api/dashboard/summary')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($login['access_token'])->postJson('/api/auth/refresh')->assertForbidden();
    }

    public function test_expired_access_token_is_rejected(): void
    {
        $nasabah = Nasabah::factory()->create();
        $login = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->json();

        $this->travel(config('ecowin.tokens.access_ttl') + 1)->minutes();

        $this->withToken($login['access_token'])->getJson('/api/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        // Refresh token masih berlaku => aplikasi bisa refresh otomatis tanpa login Google lagi.
        $this->withToken($login['refresh_token'])->postJson('/api/auth/refresh')->assertOk();
    }

    public function test_logout_revokes_tokens_of_this_device(): void
    {
        $nasabah = Nasabah::factory()->create();
        $login = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->json();

        $this->withToken($login['access_token'])->postJson('/api/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($login['access_token'])->getJson('/api/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($login['refresh_token'])->postJson('/api/auth/refresh')->assertUnauthorized();
        $this->assertTrue(AuditLog::query()->where('aksi', 'logout')->exists());
    }

    public function test_disabling_account_revokes_existing_tokens(): void
    {
        $nasabah = Nasabah::factory()->create();
        $login = $this->postJson('/api/auth/google', ['id_token' => $this->googleToken($nasabah->user->email)])->json();

        $nasabah->user->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        $this->withToken($login['access_token'])->getJson('/api/auth/me')->assertUnauthorized();
        $this->assertSame(0, $nasabah->user->tokens()->count());
    }

    public function test_login_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/google', ['id_token' => str_repeat('a', 200)]);
        }

        $this->postJson('/api/auth/google', ['id_token' => str_repeat('a', 200)])->assertStatus(429);
    }

    public function test_legacy_password_and_pin_login_endpoints_are_gone(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'a@b.c', 'password' => 'x'])->assertNotFound();
        $this->postJson('/api/auth/nasabah-login', ['username' => 'a', 'pin' => '123456'])->assertNotFound();
        $this->assertFalse(Schema::hasColumn('nasabah', 'pin'));
    }

    public function test_changing_email_releases_old_google_binding(): void
    {
        $nasabah = Nasabah::factory()->create();
        $nasabah->user->forceFill(['google_id' => 'akun-lama'])->save();

        $nasabah->user->forceFill(['email' => 'email.baru@gmail.com'])->save();

        $this->assertNull($nasabah->user->fresh()->google_id);
        $this->postJson('/api/auth/google', ['id_token' => $this->googleToken('email.baru@gmail.com', sub: 'akun-baru')])->assertOk();
    }
}
