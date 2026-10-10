<?php

namespace Tests\Feature\Auth;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Satu halaman login untuk semua role + serah-terima ke web Nasabah (PKCE) + pendaftaran mandiri.
 */
class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-very-long-random-code-verifier-0123456789-abcdefghijklmnop';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGoogle();
        config(['ecowin.web_url' => 'http://localhost:5173']);
    }

    private static function challenge(string $verifier = self::VERIFIER): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function callbackAs(string $email, array $intent = []): \Illuminate\Testing\TestResponse
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['id_token' => $this->googleToken($email, 'sub-'.md5($email), true, 'n1')])]);

        return $this->withSession([
            'google_oauth' => ['state' => 'st', 'nonce' => 'n1', 'verifier' => 'v'],
            'login_intent' => $intent + ['challenge' => null, 'register' => false],
        ])->get('/auth/google/callback?state=st&code=abc');
    }

    private function codeFrom(\Illuminate\Testing\TestResponse $response): string
    {
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('http://localhost:5173/auth/callback?code=', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        return $q['code'];
    }

    // ---------- halaman & parameter ----------

    public function test_login_page_is_single_entry_for_all_roles_with_register_link(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertDontSee('Masuk sebagai')->assertSee('Kelola Sampah.', false)->assertSee('Langkah kecil, dampak besar.')->assertSee('Lanjutkan dengan Google')
            ->assertSee('Daftar sebagai nasabah')->assertSee(route('daftar'), false);
    }

    public function test_masuk_alias_forwards_pkce_params_to_the_single_login_page(): void
    {
        $c = self::challenge();
        $this->get("/masuk?app=web&c={$c}&x=lain")->assertRedirect(route('filament.admin.auth.login', ['app' => 'web', 'c' => $c]));
    }

    public function test_redirect_keeps_only_a_valid_challenge(): void
    {
        $this->get('/auth/google/redirect?app=web&c='.self::challenge())->assertRedirect();
        $this->assertSame(self::challenge(), session('login_intent.challenge'));

        $this->get('/auth/google/redirect?app=web&c=bukan-challenge-valid')->assertRedirect();
        $this->assertNull(session('login_intent.challenge'));
    }

    // ---------- Nasabah: serah-terima ke web ----------

    public function test_nasabah_gets_one_time_code_for_web_and_no_laravel_session(): void
    {
        $nasabah = Nasabah::factory()->create();

        $response = $this->callbackAs($nasabah->user->email, ['challenge' => self::challenge()])->assertRedirect();
        $code = $this->codeFrom($response);

        $this->assertGuest();

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])
            ->assertOk()->assertJsonStructure(['access_token', 'refresh_token', 'access_expires_at'])
            ->assertJsonPath('user.role', 'nasabah')->assertJsonPath('user.email', $nasabah->user->email);
    }

    public function test_code_is_single_use(): void
    {
        $nasabah = Nasabah::factory()->create();
        $code = $this->codeFrom($this->callbackAs($nasabah->user->email, ['challenge' => self::challenge()]));

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])->assertOk();
        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])->assertStatus(401);
    }

    public function test_wrong_verifier_is_rejected_and_burns_the_code(): void
    {
        $nasabah = Nasabah::factory()->create();
        $code = $this->codeFrom($this->callbackAs($nasabah->user->email, ['challenge' => self::challenge()]));

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => str_repeat('x', 50)])->assertStatus(401);
        // Kode sudah hangus: verifier yang benar pun tidak bisa memakainya lagi.
        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])->assertStatus(401);
    }

    public function test_expired_or_unknown_code_is_rejected(): void
    {
        $this->postJson('/api/auth/exchange', ['code' => str_repeat('A', 64), 'verifier' => self::VERIFIER])->assertStatus(401);
        $this->postJson('/api/auth/exchange', ['code' => 'pendek', 'verifier' => self::VERIFIER])->assertStatus(422);

        $nasabah = Nasabah::factory()->create();
        $code = $this->codeFrom($this->callbackAs($nasabah->user->email, ['challenge' => self::challenge()]));
        Cache::flush(); // simulasi kedaluwarsa

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])->assertStatus(401);
    }

    public function test_disabled_nasabah_cannot_redeem_a_code(): void
    {
        $nasabah = Nasabah::factory()->create();
        $code = $this->codeFrom($this->callbackAs($nasabah->user->email, ['challenge' => self::challenge()]));
        $nasabah->user->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])->assertStatus(401);
    }

    public function test_nasabah_without_challenge_is_sent_to_the_web_app_login(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->callbackAs($nasabah->user->email)->assertRedirect('http://localhost:5173/masuk');
        $this->assertGuest();
    }

    public function test_staff_still_get_a_laravel_session_not_a_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->callbackAs($admin->email, ['challenge' => self::challenge()])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_unregistered_email_is_still_rejected_and_offered_registration(): void
    {
        $this->callbackAs('orang.baru@gmail.com')->assertRedirect(route('filament.admin.auth.login'))->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'orang.baru@gmail.com']);
    }

    // ---------- Pendaftaran mandiri (Google -> data diri -> akun pending, tanpa OTP) ----------

    private function startRegistration(string $email = 'warga.baru@gmail.com', ?string $challenge = null): void
    {
        $this->callbackAs($email, ['register' => true, 'challenge' => $challenge])->assertRedirect(route('daftar.lengkapi'));
    }

    private function payload(array $override = []): array
    {
        return [
            'nama' => 'Warga Baru', 'no_hp' => '081234567890', 'alamat_rt_rw' => 'Jl. Melati No. 7 RT 01/05',
            'bank_sampah_id' => BankSampah::factory()->create()->id, 'setuju' => '1', ...$override,
        ];
    }

    public function test_registration_pages_render(): void
    {
        $this->get('/daftar')->assertOk()->assertSee('Daftar dengan Google')->assertDontSee('WhatsApp');
    }

    public function test_registration_starts_from_verified_google_identity_kept_server_side(): void
    {
        $this->startRegistration();
        $this->assertSame('warga.baru@gmail.com', session('pending_registration.identity.email'));

        $this->get('/daftar/lengkapi')->assertOk()->assertSee('warga.baru@gmail.com')->assertSee('terverifikasi Google');
        $this->assertDatabaseMissing('users', ['email' => 'warga.baru@gmail.com']);
    }

    public function test_form_creates_pending_nasabah_with_forced_role(): void
    {
        $this->startRegistration();
        // Percobaan manipulasi: role, email, status, saldo dari form harus diabaikan.
        $data = $this->payload(['no_hp' => '+62 812-3456-7890']); // dinormalkan
        $this->post('/daftar/lengkapi', $data + ['role' => 'admin', 'email' => 'admin@ecowin.id', 'status_verifikasi' => 'verified', 'saldo' => 999999, 'google_id' => 'palsu'])
            ->assertRedirect('http://localhost:5173/masuk');

        $user = User::where('email', 'warga.baru@gmail.com')->firstOrFail();
        $this->assertSame('nasabah', $user->role);
        $this->assertSame($data['bank_sampah_id'], $user->bank_sampah_id);
        $this->assertSame('sub-'.md5('warga.baru@gmail.com'), $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('users', ['email' => 'admin@ecowin.id']);

        $nasabah = $user->nasabah;
        $this->assertSame('pending', $nasabah->status_verifikasi);
        $this->assertSame('081234567890', $nasabah->no_hp);
        $this->assertEquals(0, $nasabah->saldo);
        $this->assertNull($nasabah->dibuat_oleh);
        $this->assertMatchesRegularExpression('/^NSB-\d{6}$/', $nasabah->nomor_nasabah);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'daftar_mandiri', 'user_id' => $user->id]);
        $this->assertNull(session('pending_registration'));
        $this->assertGuest();
    }

    public function test_otp_pages_no_longer_exist(): void
    {
        $this->get('/daftar/verifikasi')->assertNotFound();
        $this->post('/daftar/verifikasi', ['kode' => '123456'])->assertStatus(404);
    }

    public function test_registration_from_web_app_hands_off_a_code(): void
    {
        $this->startRegistration('lewat.web@gmail.com', self::challenge());

        $response = $this->post('/daftar/lengkapi', $this->payload())->assertRedirect();
        $code = $this->codeFrom($response);

        $this->postJson('/api/auth/exchange', ['code' => $code, 'verifier' => self::VERIFIER])
            ->assertOk()->assertJsonPath('user.nasabah.status_verifikasi', 'pending');
    }

    public function test_registration_validates_input_and_active_bank(): void
    {
        $this->startRegistration();
        $inactive = BankSampah::factory()->create(['status' => 'nonaktif']);

        $this->post('/daftar/lengkapi', $this->payload(['bank_sampah_id' => $inactive->id, 'no_hp' => '12345', 'nama' => 'ab', 'setuju' => null]))
            ->assertSessionHasErrors(['bank_sampah_id', 'no_hp', 'nama', 'setuju']);

        $this->assertDatabaseCount('nasabah', 0);
    }

    public function test_duplicate_phone_number_is_rejected_even_in_other_formats(): void
    {
        Nasabah::factory()->create(['no_hp' => '081299998888']);
        $this->startRegistration();

        $this->post('/daftar/lengkapi', $this->payload(['no_hp' => '+6281299998888']))->assertSessionHasErrors('no_hp');
        $this->assertDatabaseMissing('users', ['email' => 'warga.baru@gmail.com']);
    }

    public function test_registration_form_requires_a_google_verified_session(): void
    {
        $this->get('/daftar/lengkapi')->assertRedirect(route('daftar'));
        $this->post('/daftar/lengkapi', $this->payload())->assertRedirect(route('daftar'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_session_expires(): void
    {
        $this->startRegistration();
        $this->travel(20)->minutes();

        $this->get('/daftar/lengkapi')->assertRedirect(route('daftar'));
        $this->post('/daftar/lengkapi', $this->payload())->assertRedirect(route('daftar'));
        $this->assertDatabaseMissing('users', ['email' => 'warga.baru@gmail.com']);
    }

    public function test_cannot_register_an_email_that_already_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->callbackAs($admin->email, ['register' => true])->assertRedirect(route('filament.admin.auth.login'))->assertSessionHas('auth_error');
        $this->assertNull(session('pending_registration'));
    }

    public function test_unverified_nasabah_cannot_request_withdrawal(): void
    {
        $nasabah = Nasabah::factory()->create(['saldo' => 50000]);
        $nasabah->forceFill(['status_verifikasi' => 'pending'])->save();

        $this->actingAsApi($nasabah->user)->postJson('/api/penarikan', ['jumlah' => 10000])
            ->assertStatus(422)->assertJsonValidationErrors('jumlah');

        $nasabah->forceFill(['status_verifikasi' => 'verified'])->save();
        $this->actingAsApi($nasabah->user->fresh())->postJson('/api/penarikan', ['jumlah' => 10000])->assertCreated();
    }

    public function test_petugas_verifies_pending_nasabah_with_audit_but_not_other_banks(): void
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $nasabah->forceFill(['status_verifikasi' => 'pending'])->save();

        app(\App\Services\NasabahService::class)->verifikasi($petugas, $nasabah);

        $this->assertSame('verified', $nasabah->fresh()->status_verifikasi);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'verifikasi_nasabah', 'model_id' => $nasabah->id, 'user_id' => $petugas->id]);

        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $lain->forceFill(['status_verifikasi' => 'pending'])->save();
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(\App\Services\NasabahService::class)->verifikasi($petugas, $lain);
    }

    public function test_petugas_rejects_pending_registration_which_disables_the_account(): void
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $nasabah->forceFill(['status_verifikasi' => 'pending'])->save();

        app(\App\Services\NasabahService::class)->tolak($petugas, $nasabah, 'Data tidak sesuai warga RT');

        $this->assertSame('nonaktif', $nasabah->fresh()->status);
        $this->assertFalse($nasabah->user->fresh()->isActive());
        $this->assertDatabaseHas('audit_log', ['aksi' => 'tolak_nasabah', 'model_id' => $nasabah->id, 'user_id' => $petugas->id]);
        $this->actingAsApi($nasabah->user->fresh())->getJson('/api/auth/me')->assertForbidden();
    }

    public function test_verified_nasabah_cannot_be_rejected(): void
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $nasabah->forceFill(['status_verifikasi' => 'verified'])->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\NasabahService::class)->tolak($petugas, $nasabah, 'alasan apa saja');
    }

    public function test_disabled_account_cannot_be_verified(): void
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $nasabah->forceFill(['status_verifikasi' => 'pending', 'status' => 'nonaktif'])->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\NasabahService::class)->verifikasi($petugas, $nasabah);
    }
}
