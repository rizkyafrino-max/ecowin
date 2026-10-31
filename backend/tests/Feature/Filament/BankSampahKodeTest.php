<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\BankSampahs\Pages\CreateBankSampah;
use App\Filament\Resources\BankSampahs\Pages\EditBankSampah;
use App\Models\BankSampah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kode bank sampah dibuat otomatis (BS-0001, BS-0002, ...), tidak diminta di form, dan tidak bisa diubah dari panel.
 */
class BankSampahKodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, string> */
    private function isian(string $nama, string $rt = '01'): array
    {
        return ['nama_bank_sampah' => $nama, 'rt' => $rt, 'rw' => '05', 'alamat' => 'Jl. Contoh', 'status' => 'aktif'];
    }

    public function test_form_creates_banks_with_sequential_codes_without_asking_for_a_code(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateBankSampah::class)->fillForm($this->isian('EcoWin Satu'))->call('create')->assertHasNoFormErrors();
        Livewire::test(CreateBankSampah::class)->fillForm($this->isian('EcoWin Dua', '02'))->call('create')->assertHasNoFormErrors();

        $this->assertSame(['BS-0001', 'BS-0002'], BankSampah::query()->orderBy('id')->pluck('kode')->all());
    }

    public function test_a_typed_or_tampered_code_is_ignored_by_the_form(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateBankSampah::class)->fillForm($this->isian('EcoWin Satu') + ['kode' => 'PALSU'])->call('create')->assertHasNoFormErrors();

        $this->assertSame('BS-0001', BankSampah::query()->firstOrFail()->kode);
    }

    public function test_codes_follow_the_highest_number_and_are_never_reused_and_legacy_formats_are_ignored(): void
    {
        BankSampah::factory()->create(['kode' => 'BS-RT01']);
        BankSampah::factory()->create(['kode' => 'BS-0003']);
        $this->assertSame('BS-0004', BankSampah::kodeBerikutnya());

        BankSampah::query()->where('kode', 'BS-0003')->delete();
        $this->assertSame('BS-0001', BankSampah::kodeBerikutnya(), 'tanpa kode bernomor, hitungan mulai dari awal');

        BankSampah::factory()->create(['kode' => 'BS-0010']);
        $this->assertSame('BS-0011', BankSampah::kodeBerikutnya());
    }

    public function test_an_explicit_code_is_kept_for_seeders_and_factories(): void
    {
        $bank = BankSampah::factory()->create(['kode' => 'BS-RT07']);

        $this->assertSame('BS-RT07', $bank->fresh()->kode);
    }

    public function test_editing_cannot_change_the_code(): void
    {
        $this->actingAs($this->admin);
        $bank = BankSampah::factory()->create(['kode' => 'BS-0001']);

        Livewire::test(EditBankSampah::class, ['record' => $bank->getRouteKey()])
            ->assertFormSet(['kode' => 'BS-0001'])
            ->assertFormFieldIsDisabled('kode')
            ->fillForm(['nama_bank_sampah' => 'Nama Baru', 'kode' => 'BS-9999'])
            ->call('save')
            ->assertHasNoFormErrors();

        $bank->refresh();
        $this->assertSame('Nama Baru', $bank->nama_bank_sampah);
        $this->assertSame('BS-0001', $bank->kode);
    }
}
