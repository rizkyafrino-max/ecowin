<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Alias singkat harus mengembalikan data yang sama dengan endpoint aslinya (kontrak Web = Android). */
class ApiAliasTest extends TestCase
{
    use RefreshDatabase;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->admin()->create();
        $this->nasabah = Nasabah::factory()->forBank(BankSampah::factory()->create())->create(['saldo' => 25000]);
    }

    public function test_aliases_match_the_original_endpoints_for_nasabah(): void
    {
        $this->actingAsApi($this->nasabah->user);

        foreach ([
            '/api/dashboard' => '/api/dashboard/summary',
            '/api/saldo' => '/api/me/saldo',
            '/api/saldo/history' => '/api/me/mutasi-saldo',
            '/api/qr' => '/api/me/qr',
            '/api/transaksi' => '/api/transaksi/anorganik',
            '/api/biopori' => '/api/organik/biopori',
        ] as $alias => $asli) {
            $a = $this->getJson($alias)->assertOk();
            $b = $this->getJson($asli)->assertOk();
            // Tautan paginasi memuat path masing-masing, jadi yang dibandingkan isi datanya.\n            $this->assertSame($b->json('data') ?? $b->json(), $a->json('data') ?? $a->json(), "$alias berbeda dari $asli");
        }
    }

    public function test_put_profile_works_like_patch(): void
    {
        $this->actingAsApi($this->nasabah->user);

        $this->putJson('/api/profile', ['alamat_rt_rw' => 'Jl. Anggrek No. 3 RT 02/05'])->assertOk();
        $this->assertSame('Jl. Anggrek No. 3 RT 02/05', $this->nasabah->fresh()->alamat_rt_rw);
    }

    public function test_alias_endpoints_stay_closed_to_other_roles_and_guests(): void
    {
        $this->getJson('/api/saldo')->assertUnauthorized();

        $petugas = User::factory()->petugas($this->nasabah->bankSampah)->create();
        $this->actingAsApi($petugas)->getJson('/api/saldo')->assertForbidden();
        $this->getJson('/api/biopori')->assertForbidden();
    }
}
