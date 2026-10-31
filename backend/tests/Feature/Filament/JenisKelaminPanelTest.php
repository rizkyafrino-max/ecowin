<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Nasabahs\Pages\CreateNasabah;
use App\Filament\Resources\Nasabahs\Pages\EditNasabah;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin (dan petugas pada bank sampahnya) bisa mengisi/mengubah jenis kelamin akun lain dari panel; perubahan tercatat di audit log.
 */
class JenisKelaminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BankSampah $bank;

    private User $petugas;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create();
    }

    private function log(string $aksi): ?AuditLog
    {
        return AuditLog::query()->where('aksi', $aksi)->latest('id')->first();
    }

    public function test_admin_sets_and_clears_gender_of_a_petugas_with_audit(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $this->petugas->getRouteKey()])
            ->assertFormSet(['jenis_kelamin' => null])
            ->fillForm(['jenis_kelamin' => 'P'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('P', $this->petugas->fresh()->jenis_kelamin);
        $log = $this->log('ubah_jenis_kelamin');
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(['jenis_kelamin' => null], $log->data_sebelum);
        $this->assertSame(['jenis_kelamin' => 'P'], $log->data_sesudah);

        Livewire::test(EditUser::class, ['record' => $this->petugas->getRouteKey()])
            ->assertFormSet(['jenis_kelamin' => 'P'])
            ->fillForm(['jenis_kelamin' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($this->petugas->fresh()->jenis_kelamin);
    }

    public function test_unchanged_gender_does_not_create_an_audit_entry(): void
    {
        $this->petugas->forceFill(['jenis_kelamin' => 'L'])->save();
        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $this->petugas->getRouteKey()])
            ->fillForm(['nama' => 'Nama Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('L', $this->petugas->fresh()->jenis_kelamin);
        $this->assertNull($this->log('ubah_jenis_kelamin'));
    }

    public function test_invalid_gender_values_are_rejected(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $this->petugas->getRouteKey()])
            ->fillForm(['jenis_kelamin' => 'X'])
            ->call('save')
            ->assertHasFormErrors(['jenis_kelamin']);

        $this->assertNull($this->petugas->fresh()->jenis_kelamin);
    }

    public function test_admin_creates_a_petugas_with_gender(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateUser::class)
            ->fillForm(['nama' => 'Petugas Baru', 'email' => 'petugas.baru@gmail.com', 'role' => 'petugas', 'bank_sampah_id' => $this->bank->id, 'status' => 'aktif', 'jenis_kelamin' => 'L'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'petugas.baru@gmail.com', 'jenis_kelamin' => 'L']);
    }

    public function test_admin_sets_gender_of_a_nasabah_and_the_default_avatar_follows(): void
    {
        $this->actingAs($this->admin);
        $user = $this->nasabah->user;
        $this->assertNull($user->jenis_kelamin);

        Livewire::test(EditNasabah::class, ['record' => $this->nasabah->getRouteKey()])
            ->assertFormSet(['jenis_kelamin' => null])
            ->fillForm(['jenis_kelamin' => 'P'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('P', $user->jenis_kelamin);
        $this->assertStringContainsString('avatar-perempuan', (string) $user->avatarUrl());
        $this->assertSame(['jenis_kelamin' => 'P'], $this->log('ubah_jenis_kelamin')?->data_sesudah);
    }

    public function test_petugas_can_set_gender_of_a_nasabah_of_own_bank_only(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(EditNasabah::class, ['record' => $this->nasabah->getRouteKey()])
            ->fillForm(['jenis_kelamin' => 'L'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('L', $this->nasabah->user->fresh()->jenis_kelamin);

        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();
        $this->get(\App\Filament\Resources\Nasabahs\NasabahResource::getUrl('edit', ['record' => $lain]))->assertNotFound();
        $this->assertNull($lain->user->fresh()->jenis_kelamin);
    }

    public function test_nasabah_created_in_the_panel_keeps_the_gender(): void
    {
        $this->actingAs($this->petugas);

        Livewire::test(CreateNasabah::class)
            ->fillForm(['nama' => 'Warga Baru', 'email' => 'warga.baru@gmail.com', 'no_hp' => '081211112222', 'alamat_rt_rw' => 'RT 01', 'jenis_kelamin' => 'P'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('P', Nasabah::query()->where('no_hp', '081211112222')->firstOrFail()->user->jenis_kelamin);
    }
}
