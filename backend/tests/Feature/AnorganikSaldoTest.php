<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\MutasiSaldo;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnorganikSaldoTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    private Nasabah $nasabah;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->admin()->create();
        $bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($bank)->create();
        $this->jenis = JenisSampah::factory()->create();

        // Harga bertingkat: 0–5 kg Rp1.000, 5–10 kg Rp1.500, ≥10 kg Rp2.000
        foreach ([[0, 5, 1000], [5, 10, 1500], [10, null, 2000]] as [$min, $max, $rp]) {
            HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'minimal_berat' => $min, 'maksimal_berat' => $max, 'harga_per_kg' => $rp]);
        }
    }

    /**
     * @return array<string, array{float, int, int}>
     */
    public static function tingkatanHarga(): array
    {
        return [
            '3 kg tier 1' => [3, 1000, 3000],
            '5 kg batas atas tier 1' => [5, 1000, 5000],
            '7.5 kg tier 2' => [7.5, 1500, 11250],
            '10 kg batas atas tier 2' => [10, 1500, 15000],
            '12.25 kg tier 3' => [12.25, 2000, 24500],
        ];
    }

    #[DataProvider('tingkatanHarga')]
    public function test_price_is_taken_from_database_by_weight_tier(float $berat, int $hargaPerKg, int $total): void
    {
        $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => $berat])
            ->assertCreated()
            ->assertJsonPath('data.harga_per_kg', $hargaPerKg)
            ->assertJsonPath('data.nilai_rupiah', $total);

        $this->assertEquals($total, $this->nasabah->fresh()->saldo);
    }

    public function test_client_supplied_price_or_total_is_ignored(): void
    {
        $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', [
                'nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 2,
                'harga_sampah_id' => 999, 'harga_per_kg' => 1000000, 'nilai_rupiah' => 99999999, 'bank_sampah_id' => 999,
            ])
            ->assertCreated()->assertJsonPath('data.nilai_rupiah', 2000)->assertJsonPath('data.bank_sampah_id', $this->nasabah->bank_sampah_id);
    }

    public function test_newest_price_version_wins_and_expired_prices_are_ignored(): void
    {
        HargaSampah::query()->update(['berlaku_sampai' => now()->subMinute()]);
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 4000, 'berlaku_mulai' => now()->subMinute()]);

        $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 1])
            ->assertCreated()->assertJsonPath('data.nilai_rupiah', 4000);
    }

    public function test_no_active_price_is_rejected(): void
    {
        HargaSampah::query()->update(['status' => 'nonaktif']);

        $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('jenis_sampah_id');
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function beratTidakValid(): array
    {
        return ['nol' => [0], 'negatif' => [-5], 'teks' => ['abc'], 'terlalu besar' => [999999], '3 desimal' => [1.234]];
    }

    #[DataProvider('beratTidakValid')]
    public function test_invalid_weight_is_rejected(mixed $berat): void
    {
        $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => $berat])
            ->assertStatus(422)->assertJsonValidationErrors('berat_kg');
    }

    public function test_saldo_history_is_recorded_for_each_change(): void
    {
        $this->actingAsApi($this->petugas);
        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 4])->assertCreated();
        $this->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 2])->assertCreated();

        $mutasi = MutasiSaldo::query()->where('nasabah_id', $this->nasabah->id)->orderBy('id')->get();
        $this->assertCount(2, $mutasi);
        $this->assertEquals([0, 4000], $mutasi->pluck('saldo_sebelum')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals([4000, 6000], $mutasi->pluck('saldo_sesudah')->map(fn ($v) => (float) $v)->all());

        $this->actingAsApi($this->nasabah->user)->getJson('/api/me/mutasi-saldo')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/transaksi/anorganik')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_withdrawal_full_flow_pending_approved_completed(): void
    {
        $this->setor(10);
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/penarikan', ['jumlah' => 6000])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        $this->assertEquals(15000, $this->nasabah->fresh()->saldo, 'Saldo baru berkurang setelah disetujui');
        $this->getJson('/api/me/saldo')->assertJsonPath('saldo_tersedia', 9000);

        $this->actingAsApi($this->petugas)->postJson("/api/penarikan/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertEquals(9000, $this->nasabah->fresh()->saldo);
        $this->postJson("/api/penarikan/{$id}/complete")->assertOk()->assertJsonPath('data.status', 'completed');
        $this->postJson("/api/penarikan/{$id}/approve")->assertStatus(422);

        $this->assertDatabaseHas('mutasi_saldo', ['nasabah_id' => $this->nasabah->id, 'tipe' => 'debit', 'jumlah' => 6000, 'sumber_type' => PenarikanSaldo::class]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'approve_penarikan']);
    }

    public function test_withdrawal_rejection_requires_note_and_keeps_balance(): void
    {
        $this->setor(2);
        $id = $this->actingAsApi($this->nasabah->user)->postJson('/api/penarikan', ['jumlah' => 2000])->json('data.id');

        $this->actingAsApi($this->petugas)->postJson("/api/penarikan/{$id}/reject")->assertStatus(422)->assertJsonValidationErrors('catatan');
        $this->postJson("/api/penarikan/{$id}/reject", ['catatan' => 'Data rekening tidak sesuai'])->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->assertEquals(2000, $this->nasabah->fresh()->saldo);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'reject_penarikan']);
    }

    public function test_double_spend_with_multiple_pending_requests_is_blocked(): void
    {
        $this->setor(10); // saldo 15.000
        $this->actingAsApi($this->nasabah->user);

        $this->postJson('/api/penarikan', ['jumlah' => 10000])->assertCreated();
        $this->postJson('/api/penarikan', ['jumlah' => 10000])->assertStatus(422)->assertJsonValidationErrors('jumlah');
        $this->postJson('/api/penarikan', ['jumlah' => 5000])->assertCreated();
        $this->postJson('/api/penarikan', ['jumlah' => 1000])->assertStatus(422);
    }

    public function test_withdrawal_exceeding_balance_or_below_minimum_is_rejected(): void
    {
        $this->setor(1);
        $this->actingAsApi($this->nasabah->user);

        $this->postJson('/api/penarikan', ['jumlah' => 5000])->assertStatus(422);
        $this->postJson('/api/penarikan', ['jumlah' => 500])->assertStatus(422);
        $this->postJson('/api/penarikan', ['jumlah' => -1000])->assertStatus(422);
    }

    public function test_nasabah_cannot_withdraw_for_another_nasabah(): void
    {
        $other = Nasabah::factory()->forBank($this->nasabah->bankSampah)->create();

        $this->actingAsApi($this->nasabah->user)->postJson('/api/penarikan', ['nasabah_id' => $other->id, 'jumlah' => 1000])
            ->assertStatus(422)->assertJsonValidationErrors('nasabah_id');
    }

    public function test_correction_is_decided_by_another_petugas_of_the_same_bank_without_admin(): void
    {
        $trxId = $this->setor(4); // Rp4.000
        $rekan = User::factory()->petugas($this->nasabah->bankSampah)->create();

        $koreksiId = $this->actingAsApi($this->petugas)
            ->postJson('/api/koreksi', ['tipe_transaksi' => 'anorganik', 'transaksi_id' => $trxId, 'berat_kg' => 3, 'alasan' => 'Salah timbang, seharusnya 3 kg'])
            ->assertCreated()->json('id');

        // Pengaju tidak boleh memutuskan koreksinya sendiri.
        $this->postJson("/api/koreksi/{$koreksiId}/approve")->assertForbidden();

        $this->actingAsApi($rekan)->postJson("/api/koreksi/{$koreksiId}/approve", ['catatan' => 'OK'])->assertOk()->assertJsonPath('status', 'approved');

        $this->assertEquals(3000, $this->nasabah->fresh()->saldo);
        $this->assertDatabaseHas('transaksi_anorganik', ['id' => $trxId, 'nilai_rupiah' => 3000]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'approve_koreksi_transaksi', 'user_id' => $rekan->id]);
    }
    public function test_petugas_of_another_bank_cannot_decide_the_correction(): void
    {
        $trxId = $this->setor(4);
        $id = $this->actingAsApi($this->petugas)->postJson('/api/koreksi', ['tipe_transaksi' => 'anorganik', 'transaksi_id' => $trxId, 'berat_kg' => 3, 'alasan' => 'Salah timbang, seharusnya 3 kg'])->json('id');

        $lain = User::factory()->petugas(\App\Models\BankSampah::factory()->create())->create();
        $this->actingAsApi($lain)->postJson("/api/koreksi/{$id}/approve")->assertForbidden();
        $this->assertDatabaseHas('koreksi_transaksi', ['id' => $id, 'status' => 'pending']);
    }
    private function setor(float $berat): int
    {
        $id = $this->actingAsApi($this->petugas)
            ->postJson('/api/transaksi/anorganik', ['nasabah_id' => $this->nasabah->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => $berat])
            ->assertCreated()->json('data.id');
        $this->app['auth']->forgetGuards();

        return $id;
    }
}
