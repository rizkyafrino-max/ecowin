<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AktivitasBioporis\Pages\ListAktivitasBioporis;
use App\Filament\Resources\HargaSampahs\Pages\CreateHargaSampah;
use App\Filament\Resources\KoreksiTransaksis\Pages\ListKoreksiTransaksis;
use App\Filament\Resources\Nasabahs\Pages\CreateNasabah;
use App\Filament\Resources\PenarikanSaldos\Pages\CreatePenarikanSaldo;
use App\Filament\Resources\PenarikanSaldos\Pages\ListPenarikanSaldos;
use App\Filament\Resources\TransaksiAnorganiks\Pages\CreateTransaksiAnorganik;
use App\Filament\Resources\TransaksiAnorganiks\Pages\ListTransaksiAnorganiks;
use App\Filament\Resources\TransaksiOrganiks\Pages\CreateTransaksiOrganik;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AktivitasBiopori;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TitikBiopori;
use App\Models\TransaksiAnorganik;
use App\Models\User;
use App\Services\TransaksiService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private BankSampah $bankA;

    private BankSampah $bankB;

    private Nasabah $nasabahA;

    private Nasabah $nasabahB;

    private JenisSampah $jenis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create();
        $this->bankB = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bankA)->create();
        $this->nasabahA = Nasabah::factory()->forBank($this->bankA)->create();
        $this->nasabahB = Nasabah::factory()->forBank($this->bankB)->create();
        $this->jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $this->jenis->id, 'harga_per_kg' => 2500]);
    }

    public function test_petugas_records_anorganik_transaction_with_server_side_price(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiAnorganik::class)
            ->fillForm(['nasabah_id' => $this->nasabahA->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 4])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transaksi_anorganik', ['nasabah_id' => $this->nasabahA->id, 'nilai_rupiah' => 10000, 'dicatat_oleh' => $this->petugas->id]);
        $this->assertEquals(10000, $this->nasabahA->fresh()->saldo);
    }

    public function test_petugas_cannot_record_transaction_for_other_bank_nasabah(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiAnorganik::class)
            ->fillForm(['nasabah_id' => $this->nasabahB->id, 'jenis_sampah_id' => $this->jenis->id, 'berat_kg' => 4])
            ->call('create')
            ->assertHasFormErrors(['nasabah_id']);

        $this->assertDatabaseCount('transaksi_anorganik', 0);
    }

    public function test_petugas_cannot_record_organik_for_other_bank_nasabah(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateTransaksiOrganik::class)
            ->fillForm(['nasabah_id' => $this->nasabahB->id, 'tanggal' => now()->toDateString(), 'jenis_organik' => 'Daun', 'berat_kg' => 2, 'metode_pengolahan' => 'komposter', 'checklist_bebas_plastik' => true, 'checklist_bebas_logam' => true])
            ->call('create')
            ->assertHasFormErrors(['nasabah_id']);

        $this->assertDatabaseCount('transaksi_organik', 0);
    }

    public function test_withdrawal_created_in_panel_is_always_pending(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabahA, $this->jenis, 4);
        $this->actingAs($this->petugas);

        Livewire::test(CreatePenarikanSaldo::class)
            ->fillForm(['nasabah_id' => $this->nasabahA->id, 'jumlah' => 5000, 'status' => 'completed', 'diproses_oleh' => $this->admin->id, 'bank_sampah_id' => $this->bankB->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('penarikan_saldo', ['nasabah_id' => $this->nasabahA->id, 'status' => 'pending', 'bank_sampah_id' => $this->bankA->id, 'diproses_oleh' => null]);
        $this->assertEquals(10000, $this->nasabahA->fresh()->saldo);
    }

    public function test_petugas_approves_withdrawal_from_table(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabahA, $this->jenis, 4);
        $p = new PenarikanSaldo;
        $p->forceFill(['nasabah_id' => $this->nasabahA->id, 'bank_sampah_id' => $this->bankA->id, 'jumlah' => 4000, 'status' => 'pending'])->save();

        $this->actingAs($this->petugas);
        Livewire::test(ListPenarikanSaldos::class)
            ->callAction(TestAction::make('approve')->table($p), ['catatan' => 'Tunai'])
            ->assertHasNoErrors();

        $this->assertSame('approved', $p->fresh()->status);
        $this->assertEquals(6000, $this->nasabahA->fresh()->saldo);
    }

    public function test_petugas_does_not_see_other_bank_rows_in_tables(): void
    {
        app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabahA, $this->jenis, 1);
        $trxB = app(TransaksiService::class)->catatAnorganik($this->admin, $this->nasabahB, $this->jenis, 1);

        $this->actingAs($this->petugas);
        Livewire::test(ListTransaksiAnorganiks::class)
            ->assertCanSeeTableRecords(TransaksiAnorganik::query()->where('bank_sampah_id', $this->bankA->id)->get())
            ->assertCanNotSeeTableRecords([$trxB]);
    }

    public function test_petugas_verifies_biopori_from_table(): void
    {
        $titik = TitikBiopori::factory()->forBank($this->bankA)->create();
        $a = new AktivitasBiopori;
        $a->forceFill(['nasabah_id' => $this->nasabahA->id, 'bank_sampah_id' => $this->bankA->id, 'titik_biopori_id' => $titik->id, 'tanggal_pemasukan' => now(), 'berat_kg' => 1, 'foto_bukti_path' => 'x.jpg', 'status' => 'pending'])->save();

        $this->actingAs($this->petugas);
        Livewire::test(ListAktivitasBioporis::class)
            ->callAction(TestAction::make('approve')->table($a), ['catatan' => 'OK']);

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame($this->petugas->id, $a->fresh()->diperiksa_oleh);
    }

    public function test_corrections_are_decided_by_another_petugas_of_the_same_bank_in_panel(): void
    {
        $trx = app(TransaksiService::class)->catatAnorganik($this->petugas, $this->nasabahA, $this->jenis, 4);
        $this->actingAs($this->petugas);

        Livewire::test(ListTransaksiAnorganiks::class)
            ->callAction(TestAction::make('ajukan_koreksi')->table($trx), ['berat_kg' => 2, 'alasan' => 'Timbangan belum dikalibrasi']);
        $koreksi = KoreksiTransaksi::query()->firstOrFail();

        // Pengaju tidak melihat tombol; rekan petugas di Bank Sampah yang sama bisa menyetujui.
        Livewire::test(ListKoreksiTransaksis::class)->assertActionHidden(TestAction::make('approve')->table($koreksi));

        $this->actingAs(User::factory()->petugas($this->petugas->bankSampah)->create());
        Livewire::test(ListKoreksiTransaksis::class)->callAction(TestAction::make('approve')->table($koreksi), ['catatan' => 'Disetujui']);

        $this->assertSame('approved', $koreksi->fresh()->status);
        $this->assertEquals(5000, $this->nasabahA->fresh()->saldo);
    }

    public function test_petugas_registers_nasabah_bound_to_own_bank_with_google_email(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateNasabah::class)
            ->fillForm(['nama' => 'Warga Baru', 'email' => 'warga.baru@gmail.com', 'no_hp' => '081211112222', 'alamat_rt_rw' => 'RT 01', 'bank_sampah_id' => $this->bankB->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $n = Nasabah::query()->where('no_hp', '081211112222')->firstOrFail();
        $this->assertSame($this->bankA->id, $n->bank_sampah_id);
        $this->assertSame('nasabah', $n->user->role);
        $this->assertNotNull($n->nomor_nasabah);
    }

    public function test_admin_creates_petugas_with_google_email_only(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateUser::class)
            ->fillForm(['nama' => 'Petugas Baru', 'email' => 'Petugas.Baru@Gmail.com', 'role' => 'petugas', 'bank_sampah_id' => $this->bankB->id, 'status' => 'aktif'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'petugas.baru@gmail.com', 'role' => 'petugas', 'bank_sampah_id' => $this->bankB->id, 'password' => null]);
    }

    public function test_admin_cannot_demote_or_disable_self(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['role' => 'petugas', 'bank_sampah_id' => $this->bankA->id, 'status' => 'nonaktif'])
            ->call('save');

        $this->assertSame('admin', $this->admin->fresh()->role);
        $this->assertSame('aktif', $this->admin->fresh()->status);
    }

    public function test_price_creator_is_taken_from_session(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateHargaSampah::class)
            ->fillForm(['jenis_sampah_id' => $this->jenis->id, 'kondisi' => 'Utuh', 'minimal_berat' => 0, 'harga_per_kg' => 3000, 'berlaku_mulai' => now()->toDateTimeString(), 'status' => 'aktif'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('harga_sampah', ['harga_per_kg' => 3000, 'dibuat_oleh' => $this->admin->id]);
    }

    public function test_petugas_verifies_and_rejects_new_nasabah_from_the_panel(): void
    {
        $verif = \App\Models\Nasabah::factory()->forBank($this->petugas->bankSampah)->create();
        $verif->forceFill(['status_verifikasi' => 'pending'])->save();
        $tolak = \App\Models\Nasabah::factory()->forBank($this->petugas->bankSampah)->create();
        $tolak->forceFill(['status_verifikasi' => 'pending'])->save();
        $lain = \App\Models\Nasabah::factory()->forBank(\App\Models\BankSampah::factory()->create())->create();
        $lain->forceFill(['status_verifikasi' => 'pending'])->save();
        $this->actingAs($this->petugas);

        $page = Livewire::test(\App\Filament\Resources\Nasabahs\Pages\ListNasabahs::class);
        $page->assertActionVisible(TestAction::make('verifikasi')->table($verif))
            ->assertActionHidden(TestAction::make('verifikasi')->table($this->nasabahA))
            ->callAction(TestAction::make('verifikasi')->table($verif));
        $this->assertSame('verified', $verif->fresh()->status_verifikasi);

        $page->callAction(TestAction::make('tolak')->table($tolak), ['alasan' => 'Bukan warga RT ini']);
        $this->assertSame('nonaktif', $tolak->fresh()->status);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'tolak_nasabah', 'model_id' => $tolak->id]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'verifikasi_nasabah', 'model_id' => $verif->id]);
    }
}
