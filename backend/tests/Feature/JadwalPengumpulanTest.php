<?php

namespace Tests\Feature;

use App\Filament\Resources\JadwalPengumpulans\Pages\CreateJadwalPengumpulan;
use App\Filament\Resources\JadwalPengumpulans\Pages\EditJadwalPengumpulan;
use App\Models\BankSampah;
use App\Models\JadwalPengumpulan;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\User;
use App\Services\JadwalPengumpulanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** Jadwal pengumpulan per bank sampah (Admin) dan pengingat otomatis ke nasabah. */
class JadwalPengumpulanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BankSampah $bankA;

    private BankSampah $bankB;

    private Nasabah $a;

    private Nasabah $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bankA = BankSampah::factory()->create();
        $this->bankB = BankSampah::factory()->create();
        $this->a = Nasabah::factory()->forBank($this->bankA)->create();
        $this->b = Nasabah::factory()->forBank($this->bankB)->create();
    }

    private function jadwal(array $o = []): JadwalPengumpulan
    {
        $j = new JadwalPengumpulan;
        $j->forceFill(array_merge([
            'bank_sampah_id' => $this->bankA->id, 'dibuat_oleh' => $this->admin->id,
            'hari' => [1, 4], 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'lokasi' => 'Pos RW 05',
            'catatan' => null, 'pengingat_menit' => 120, 'aktif' => true,
        ], $o))->save();

        return $j;
    }

    private function notif(Nasabah $n): int
    {
        return Notifikasi::query()->where('user_id', $n->user_id)->where('tipe', 'pengumuman')->count();
    }

    /** Senin, 26 Oktober 2026 pukul $jam (zona waktu aplikasi). */
    private function senin(string $jam): Carbon
    {
        return Carbon::parse("2026-10-26 $jam", config('app.timezone'));
    }

    public function test_pengingat_dikirim_ke_bank_sampah_yang_tepat_pada_hari_dan_jendela_waktunya(): void
    {
        $this->jadwal();
        $svc = app(JadwalPengumpulanService::class);

        $this->assertSame(0, $svc->kirimYangJatuhTempo($this->senin('05:59')), 'terlalu dini (pengingat mulai 06.00)');
        $this->assertSame(0, $svc->kirimYangJatuhTempo(Carbon::parse('2026-10-27 07:00', config('app.timezone'))), 'Selasa bukan hari jadwal');

        $this->assertSame(1, $svc->kirimYangJatuhTempo($this->senin('06:30')));
        $this->assertSame(1, $this->notif($this->a));
        $this->assertSame(0, $this->notif($this->b), 'bank sampah lain tidak menerima');

        $n = Notifikasi::query()->where('user_id', $this->a->user_id)->first();
        $this->assertSame('Jadwal pengumpulan sampah hari ini', $n->judul);
        $this->assertStringContainsString('08.00–10.00', $n->isi);
        $this->assertStringContainsString('Pos RW 05', $n->isi);
    }

    public function test_sekali_sehari_walau_penjadwal_berjalan_berulang(): void
    {
        $j = $this->jadwal();
        $svc = app(JadwalPengumpulanService::class);

        $this->assertSame(1, $svc->kirimYangJatuhTempo($this->senin('06:00')));
        $this->assertSame(0, $svc->kirimYangJatuhTempo($this->senin('06:01')));
        $this->assertSame(0, $svc->kirimYangJatuhTempo($this->senin('07:30')));
        $this->assertSame(1, $this->notif($this->a));
        $this->assertSame('2026-10-26', $j->fresh()->terakhir_kirim_tanggal->toDateString());

        // hari jadwal berikutnya (Kamis) terkirim lagi
        $this->assertSame(1, $svc->kirimYangJatuhTempo(Carbon::parse('2026-10-29 06:10', config('app.timezone'))));
        $this->assertSame(2, $this->notif($this->a));
    }

    public function test_tidak_mengingatkan_setelah_kegiatan_selesai_dan_tidak_untuk_jadwal_nonaktif(): void
    {
        $j = $this->jadwal();
        $svc = app(JadwalPengumpulanService::class);

        $this->assertSame(0, $svc->kirimYangJatuhTempo($this->senin('10:30')), 'kegiatan sudah selesai: jangan mengingatkan');

        $j->forceFill(['aktif' => false])->save();
        $this->assertSame(0, $svc->kirimYangJatuhTempo($this->senin('07:00')), 'jadwal nonaktif');
        $this->assertSame(0, $this->notif($this->a));
    }

    public function test_pengingat_tercatat_sebagai_pengumuman_otomatis_dan_hanya_ke_nasabah_aktif(): void
    {
        $nonaktif = Nasabah::factory()->forBank($this->bankA)->create(['status' => 'nonaktif']);
        $j = $this->jadwal();

        app(JadwalPengumpulanService::class)->kirimYangJatuhTempo($this->senin('07:00'));

        $p = Pengumuman::query()->firstOrFail();
        $this->assertSame($j->id, $p->jadwal_pengumpulan_id);
        $this->assertSame($this->bankA->id, $p->bank_sampah_id);
        $this->assertSame(1, $p->jumlah_penerima);
        $this->assertSame(0, $this->notif($nonaktif));
    }

    public function test_perintah_artisan_berjalan(): void
    {
        $this->jadwal(['hari' => [1, 2, 3, 4, 5, 6, 7], 'jam_mulai' => '00:00:00', 'jam_selesai' => '23:59:00', 'pengingat_menit' => 720]);
        $this->artisan('ecowin:kirim-jadwal-pengumpulan')->assertSuccessful();
        $this->artisan('ecowin:kirim-jadwal-pengumpulan')->assertSuccessful();

        $this->assertSame(1, $this->notif($this->a));
    }

    public function test_admin_membuat_mengubah_dan_mengabarkan_perubahan_jadwal(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreateJadwalPengumpulan::class)
            ->fillForm(['bank_sampah_id' => $this->bankA->id, 'hari' => [2, 5], 'jam_mulai' => '09:00', 'jam_selesai' => '11:00', 'lokasi' => 'Balai Desa', 'pengingat_menit' => 60, 'aktif' => true])
            ->call('create')->assertHasNoFormErrors();

        $j = JadwalPengumpulan::query()->firstOrFail();
        $this->assertSame([2, 5], $j->hari);
        $this->assertSame($this->admin->id, $j->dibuat_oleh);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'buat_jadwal_pengumpulan', 'user_id' => $this->admin->id]);

        Livewire::test(EditJadwalPengumpulan::class, ['record' => $j->getKey()])
            ->fillForm(['lokasi' => 'Pos RW 07', 'hari' => [3]])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Pos RW 07', $j->fresh()->lokasi);
        $this->assertSame([3], $j->fresh()->hari);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'ubah_jadwal_pengumpulan']);

        app(JadwalPengumpulanService::class)->umumkanPerubahan($j->fresh(), $this->admin);
        $n = Notifikasi::query()->where('user_id', $this->a->user_id)->where('judul', 'Perubahan jadwal pengumpulan sampah')->firstOrFail();
        $this->assertStringContainsString('Rabu', $n->isi);
        $this->assertStringContainsString('Pos RW 07', $n->isi);
        $this->assertSame(0, $this->notif($this->b));
    }

    public function test_validasi_formulir_dan_akses_hanya_admin(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(CreateJadwalPengumpulan::class)
            ->fillForm(['bank_sampah_id' => $this->bankA->id, 'hari' => [], 'jam_mulai' => '09:00', 'jam_selesai' => '08:00'])
            ->call('create')->assertHasFormErrors(['hari', 'jam_selesai']);

        $petugas = User::factory()->petugas($this->bankA)->create();
        $j = $this->jadwal();
        foreach (['viewAny' => JadwalPengumpulan::class, 'create' => JadwalPengumpulan::class] as $aksi => $arg) {
            $this->assertFalse($petugas->can($aksi, $arg), "petugas tidak boleh $aksi");
            $this->assertFalse($this->a->user->can($aksi, $arg), "nasabah tidak boleh $aksi");
        }
        $this->assertFalse($petugas->can('update', $j));
        $this->assertFalse($petugas->can('delete', $j));
        $this->assertTrue($this->admin->can('update', $j));
    }
}
