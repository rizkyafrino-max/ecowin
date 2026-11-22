<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Daftar penarikan untuk petugas (aplikasi Android): menyebut nama nasabah dan bisa difilter menurut status. */
class PenarikanPetugasListTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_sees_nasabah_name_and_filters_by_status_but_only_own_bank(): void
    {
        User::factory()->admin()->create();
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $nasabah = Nasabah::factory()->forBank($bank)->create();
        $nasabah->forceFill(['saldo' => 50000])->save();
        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $lain->forceFill(['saldo' => 50000])->save();

        $this->actingAsApi($nasabah->user)->postJson('/api/penarikan', ['jumlah' => 10000])->assertCreated();
        $this->actingAsApi($lain->user)->postJson('/api/penarikan', ['jumlah' => 10000])->assertCreated();

        $rows = $this->actingAsApi($petugas)->getJson('/api/penarikan?status=pending')->assertOk()->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame($nasabah->nama, $rows[0]['nasabah']);
        $this->assertSame($nasabah->nomor_nasabah, $rows[0]['nomor_nasabah']);
        $this->assertSame(10000, $rows[0]['jumlah']);
    }

    public function test_nasabah_list_does_not_include_name_fields(): void
    {
        User::factory()->admin()->create();
        $nasabah = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $nasabah->forceFill(['saldo' => 50000])->save();
        $this->actingAsApi($nasabah->user)->postJson('/api/penarikan', ['jumlah' => 10000])->assertCreated();

        $row = $this->actingAsApi($nasabah->user)->getJson('/api/penarikan')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('nasabah', $row);
        $this->assertArrayNotHasKey('nomor_nasabah', $row);
    }
}
