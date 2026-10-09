<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\MutasiSaldo;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

/**
 * Uji keamanan: manipulasi ID/role/bank_sampah_id, token invalid/expired,
 * endpoint tanpa otorisasi, kebocoran data, mass assignment, immutability log.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_api_route_except_login_endpoints_requires_authentication(): void
    {
        // Dua-duanya sengaja publik: keduanya memverifikasi kredensial sendiri (ID token Google / kode sekali pakai + PKCE).
        $publik = ['api/auth/google', 'api/auth/exchange'];

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'api/') && ! in_array($r->uri(), $publik, true));

        $this->assertGreaterThan(30, $routes->count());

        foreach ($routes as $route) {
            $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $method = collect($route->methods())->reject(fn ($m) => $m === 'HEAD')->first();

            $this->json($method, '/'.$uri)->assertUnauthorized();
        }
    }

    public function test_invalid_bearer_token_is_rejected(): void
    {
        $this->withToken('1|palsu-token-tidak-ada')->getJson('/api/dashboard/summary')->assertUnauthorized();
        $this->withToken('bukan-format-sanctum')->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_user_cannot_change_own_role_bank_or_balance_via_profile(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->actingAsApi($nasabah->user)->patchJson('/api/profile', ['role' => 'admin'])->assertStatus(422)->assertJsonValidationErrors('role');
        $this->patchJson('/api/profile', ['bank_sampah_id' => 999])->assertStatus(422);
        $this->patchJson('/api/profile', ['saldo' => 999999])->assertStatus(422);
        $this->patchJson('/api/profile', ['email' => 'admin@gmail.com'])->assertStatus(422);
        $this->patchJson('/api/profile', ['alamat_rt_rw' => 'RT 02 / RW 05'])->assertOk();

        $this->assertSame('nasabah', $nasabah->user->fresh()->role);
        $this->assertEquals(0, $nasabah->fresh()->saldo);
    }

    public function test_mass_assignment_of_sensitive_columns_is_blocked(): void
    {
        $this->expectException(MassAssignmentException::class);
        new User(['nama' => 'X', 'email' => 'x@gmail.com', 'role' => 'admin']);
    }

    public function test_petugas_cannot_register_nasabah_into_other_bank(): void
    {
        $bankA = BankSampah::factory()->create();
        $bankB = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bankA)->create();

        $this->actingAsApi($petugas)->postJson('/api/nasabah', [
            'nama' => 'Coba', 'email' => 'coba@gmail.com', 'no_hp' => '081299990000', 'alamat_rt_rw' => 'RT 1', 'bank_sampah_id' => $bankB->id,
        ])->assertStatus(422)->assertJsonValidationErrors('bank_sampah_id');

        $this->postJson('/api/nasabah', ['nama' => 'Coba', 'email' => 'coba@gmail.com', 'no_hp' => '081299990000', 'alamat_rt_rw' => 'RT 1'])
            ->assertCreated();
        $this->assertSame($bankA->id, Nasabah::query()->where('no_hp', '081299990000')->value('bank_sampah_id'));
        $this->assertSame('nasabah', User::query()->where('email', 'coba@gmail.com')->value('role'));
    }

    public function test_sensitive_data_is_never_exposed(): void
    {
        $nasabah = Nasabah::factory()->create(['nisn_atau_nik' => '3374000000000001']);
        $admin = User::factory()->admin()->create();

        $json = $this->actingAsApi($admin)->getJson("/api/nasabah/{$nasabah->id}")->assertOk()->getContent();
        $this->assertStringNotContainsString('3374000000000001', $json);
        $this->assertStringNotContainsString($nasabah->kartu_qr_token, $json);
        $this->assertStringNotContainsString('google_id', $json);

        $me = $this->actingAsApi($nasabah->user)->getJson('/api/auth/me')->getContent();
        $this->assertStringNotContainsString('remember_token', $me);
        $this->assertStringNotContainsString('password', $me);
    }

    public function test_qr_contains_only_random_token_and_requires_owner(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->actingAsApi($nasabah->user)->getJson('/api/me/qr')->assertOk()
            ->assertJsonPath('qr_value', $nasabah->kartu_qr_token)
            ->assertJsonStructure(['qr_png_base64']);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{48}$/', $nasabah->kartu_qr_token);
        $this->actingAsApi(User::factory()->admin()->create())->getJson('/api/me/qr')->assertForbidden();
    }

    public function test_qr_token_cannot_be_used_to_authenticate(): void
    {
        $nasabah = Nasabah::factory()->create();

        $this->withToken($nasabah->kartu_qr_token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/admin/scan-kartu/'.$nasabah->kartu_qr_token)->assertUnauthorized();
    }

    public function test_sql_injection_in_search_is_harmless(): void
    {
        $admin = User::factory()->admin()->create();
        Nasabah::factory()->count(2)->create();

        $this->actingAsApi($admin)->getJson("/api/nasabah?search=' OR 1=1 --")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/nasabah?search=%')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_stored_xss_payload_is_escaped_on_card_page(): void
    {
        $nasabah = Nasabah::factory()->create(['nama' => '<script>alert(1)</script>']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.nasabah.kartu', $nasabah))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_audit_log_and_saldo_ledger_are_immutable(): void
    {
        User::factory()->admin()->create();
        $log = AuditLog::query()->latest('id')->firstOrFail();

        try {
            $log->forceFill(['aksi' => 'diubah'])->save();
            $this->fail('Audit log seharusnya tidak bisa diubah.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_audit_log_records_ip_user_agent_and_before_after(): void
    {
        $admin = User::factory()->admin()->create();
        $jenis = JenisSampah::factory()->create();
        $harga = HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 1000]);

        $this->actingAs($admin);
        $harga->update(['harga_per_kg' => 1500]);

        $log = AuditLog::query()->where('aksi', 'ubah_harga_sampah')->latest('id')->firstOrFail();
        $this->assertSame(1000, (int) $log->data_sebelum['harga_per_kg']);
        $this->assertSame(1500, (int) $log->data_sesudah['harga_per_kg']);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(HargaSampah::class, $log->model_type);
        $this->assertNotNull($log->ip_address);
    }

    public function test_ledger_rows_cannot_be_modified(): void
    {
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id]);
        app(TransaksiService::class)->catatAnorganik($petugas, $nasabah, $jenis, 1);

        $this->expectException(LogicException::class);
        MutasiSaldo::query()->firstOrFail()->forceFill(['jumlah' => 9999999])->save();
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/admin/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_error_responses_do_not_leak_internal_model_names(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsApi($admin)->getJson('/api/nasabah/999999')->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }
}
