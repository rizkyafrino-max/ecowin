<?php

namespace Tests\Feature;

use App\Filament\Resources\Nasabahs\Pages\ListNasabahs;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TransaksiAnorganik;
use App\Models\User;
use App\Services\PenghapusanAkunService;
use App\Services\TransaksiService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hapus data akun yang sudah tidak aktif (opsional, hanya Admin): data pribadi dihapus,
 * riwayat keuangan dan audit log tetap utuh secara anonim.
 */
class PenghapusanAkunTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private BankSampah $bank;

    private Nasabah $nasabah;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create(['nama' => 'Budi Rahasia']);
        $this->jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 2000]);
    }

    private function nonaktifkan(Nasabah $n): Nasabah
    {
        $n->forceFill(['status' => 'nonaktif'])->save();
        $n->user?->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        return $n->fresh();
    }

    private function service(): PenghapusanAkunService
    {
        return app(PenghapusanAkunService::class);
    }

    public function test_admin_deletes_inactive_nasabah_personal_data_but_history_stays_anonymous(): void
    {
        $trx = app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 4);
        $this->nasabah->forceFill(['saldo' => 0])->save(); // saldo sudah ditarik habis
        $n = $this->nonaktifkan($this->nasabah->fresh());
        $user = $n->user;
        $user->createToken('hp');
        $lamaToken = $n->kartu_qr_token;

        $this->service()->hapusNasabah($this->admin, $n);

        $this->assertSoftDeleted('nasabah', ['id' => $n->id]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame(0, Nasabah::query()->count());

        $raw = Nasabah::withTrashed()->find($n->id);
        $this->assertSame('Nasabah dihapus', $raw->nama);
        $this->assertSame('-', $raw->alamat_rt_rw);
        $this->assertNull($raw->nisn_atau_nik);
        $this->assertNotSame($lamaToken, $raw->kartu_qr_token);
        $this->assertStringNotContainsString('Budi', (string) json_encode($raw->getAttributes()));

        $u = User::withTrashed()->find($user->id);
        $this->assertSame('Pengguna dihapus', $u->nama);
        $this->assertStringEndsWith('@ecowin.invalid', $u->email);
        $this->assertNull($u->google_id);
        $this->assertSame(0, $u->tokens()->count());

        // Riwayat tetap: transaksi masih menunjuk ke nasabah (anonim) dan terbaca.
        $this->assertSame('Nasabah dihapus', TransaksiAnorganik::query()->with('nasabah')->find($trx->id)->nasabah->nama);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'hapus_nasabah', 'user_id' => $this->admin->id]);
        $log = DB::table('audit_log')->where('aksi', 'hapus_nasabah')->first();
        $this->assertStringNotContainsString('Budi', json_encode($log));
    }

    public function test_active_nasabah_cannot_be_deleted(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->hapusNasabah($this->admin, $this->nasabah);
    }

    public function test_nasabah_with_balance_or_pending_withdrawal_cannot_be_deleted(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 4);
        $n = $this->nonaktifkan($this->nasabah->fresh());

        try {
            $this->service()->hapusNasabah($this->admin, $n);
            $this->fail('Saldo > 0 seharusnya menghalangi penghapusan.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Saldo', $e->errors()['akun'][0]);
        }

        $n->forceFill(['saldo' => 0])->save();
        $p = new PenarikanSaldo;
        $p->forceFill(['nasabah_id' => $n->id, 'bank_sampah_id' => $this->bank->id, 'jumlah' => 1000, 'status' => 'pending'])->save();

        try {
            $this->service()->hapusNasabah($this->admin, $n->fresh());
            $this->fail('Penarikan pending seharusnya menghalangi penghapusan.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('penarikan', strtolower($e->errors()['akun'][0]));
        }

        $this->assertDatabaseHas('nasabah', ['id' => $n->id, 'deleted_at' => null]);
    }

    public function test_only_admin_can_delete(): void
    {
        $n = $this->nonaktifkan($this->nasabah->fresh());

        $this->assertFalse($this->petugas->can('delete', $n));
        $this->assertTrue($this->admin->can('delete', $n));

        $this->expectException(AuthorizationException::class);
        $this->service()->hapusNasabah($this->petugas, $n);
    }

    public function test_admin_deletes_inactive_petugas_and_work_history_stays(): void
    {
        $trx = app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabah, $this->jenis, 2);
        $this->petugas->forceFill(['status' => User::STATUS_NONAKTIF])->save();
        $this->petugas->createToken('web');

        $this->service()->hapusPetugas($this->admin, $this->petugas->fresh());

        $this->assertSoftDeleted('users', ['id' => $this->petugas->id]);
        $u = User::withTrashed()->find($this->petugas->id);
        $this->assertSame('Petugas dihapus', $u->nama);
        $this->assertSame(0, $u->tokens()->count());
        $this->assertSame('Petugas dihapus', TransaksiAnorganik::query()->with('pencatat')->find($trx->id)->pencatat->nama);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'hapus_petugas']);
    }

    public function test_cannot_delete_active_petugas_self_or_admin(): void
    {
        foreach ([$this->petugas, $this->admin] as $target) {
            try {
                $this->service()->hapusPetugas($this->admin, $target);
                $this->fail('Seharusnya ditolak.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors()['akun']);
            }
        }
        $this->assertFalse($this->admin->can('delete', $this->admin));
        $this->assertFalse($this->admin->can('delete', $this->petugas));
    }

    public function test_panel_action_deletes_for_admin_and_is_hidden_for_petugas(): void
    {
        $n = $this->nonaktifkan($this->nasabah->fresh());

        $this->actingAs($this->petugas);
        Livewire::test(ListNasabahs::class)->assertActionHidden(TestAction::make('hapus_data')->table($n));

        $this->actingAs($this->admin);
        Livewire::test(ListNasabahs::class)
            ->callAction(TestAction::make('hapus_data')->table($n))
            ->assertHasNoErrors();

        $this->assertSoftDeleted('nasabah', ['id' => $n->id]);
    }

    public function test_panel_action_for_petugas_in_users_list(): void
    {
        $this->petugas->forceFill(['status' => User::STATUS_NONAKTIF])->save();
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('hapus_data')->table($this->admin))
            ->callAction(TestAction::make('hapus_data')->table($this->petugas->fresh()))
            ->assertHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $this->petugas->id]);
    }
}
