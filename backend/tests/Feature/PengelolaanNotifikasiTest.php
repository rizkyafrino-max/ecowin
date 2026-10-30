<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menghapus notifikasi, pembersihan otomatis 30 hari, dan batas tampilan riwayat pengumuman untuk Petugas. */
class PengelolaanNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private BankSampah $bank;

    private Nasabah $a;

    private Nasabah $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bank = BankSampah::factory()->create();
        $this->petugas = User::factory()->petugas($this->bank)->create();
        $this->a = Nasabah::factory()->forBank($this->bank)->create();
        $this->b = Nasabah::factory()->forBank($this->bank)->create();
    }

    private function buat(Nasabah $n, int $jumlah, bool $dibaca = false): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            app(NotifikasiService::class)->kirim($n->user, 'pengumuman', "Judul $i", "Isi $i", 'beranda');
        }
        if ($dibaca) {
            Notifikasi::query()->where('user_id', $n->user_id)->update(['dibaca_at' => now()]);
        }
    }

    public function test_nasabah_menghapus_satu_notifikasi_miliknya_saja(): void
    {
        $this->buat($this->a, 2);
        $this->buat($this->b, 1);
        $milikA = Notifikasi::query()->where('user_id', $this->a->user_id)->first();
        $milikB = Notifikasi::query()->where('user_id', $this->b->user_id)->first();

        $this->actingAsApi($this->a->user)->deleteJson("/api/notifikasi/{$milikB->id}")->assertNotFound(); // milik orang lain
        $this->assertDatabaseHas('notifikasi', ['id' => $milikB->id]);

        $this->actingAsApi($this->a->user)->deleteJson("/api/notifikasi/{$milikA->id}")->assertOk()->assertJsonPath('belum_dibaca', 1);
        $this->assertDatabaseMissing('notifikasi', ['id' => $milikA->id]);
    }

    public function test_hapus_semua_dan_hanya_yang_sudah_dibaca(): void
    {
        $this->buat($this->a, 2, dibaca: true);
        $this->buat($this->a, 3);
        $this->buat($this->b, 2);

        $api = $this->actingAsApi($this->a->user);
        $api->deleteJson('/api/notifikasi?hanya_dibaca=1')->assertOk()->assertJsonPath('dihapus', 2)->assertJsonPath('belum_dibaca', 3);
        $this->assertSame(3, Notifikasi::query()->where('user_id', $this->a->user_id)->count());

        $this->actingAsApi($this->a->user)->deleteJson('/api/notifikasi')->assertOk()->assertJsonPath('dihapus', 3)->assertJsonPath('belum_dibaca', 0);
        $this->assertSame(0, Notifikasi::query()->where('user_id', $this->a->user_id)->count());
        $this->assertSame(2, Notifikasi::query()->where('user_id', $this->b->user_id)->count(), 'milik nasabah lain tidak tersentuh');
    }

    public function test_hapus_notifikasi_butuh_login_dan_peran_nasabah(): void
    {
        $this->deleteJson('/api/notifikasi')->assertUnauthorized();
        $this->actingAsApi($this->petugas)->deleteJson('/api/notifikasi')->assertForbidden();
    }

    public function test_pembersihan_menghapus_notifikasi_dan_riwayat_pengumuman_lebih_dari_30_hari(): void
    {
        $this->buat($this->a, 3);
        [$tua, $baru, $lewat] = Notifikasi::query()->orderBy('id')->get()->all();
        $tua->forceFill(['created_at' => now()->subDays(31)])->save();
        $baru->forceFill(['created_at' => now()->subDays(29)])->save();

        $buat = function (string $judul, ?\DateTimeInterface $terkirim) {
            $p = new Pengumuman;
            $p->forceFill(['dibuat_oleh' => $this->admin->id, 'bank_sampah_id' => $this->bank->id, 'judul' => $judul, 'isi' => 'x', 'kirim_pada' => now(), 'terkirim_at' => $terkirim])->save();

            return $p;
        };
        $lamaTerkirim = $buat('lama terkirim', now()->subDays(31));
        $baruTerkirim = $buat('baru terkirim', now()->subDays(10));
        $terjadwal = $buat('terjadwal lama', null);
        $terjadwal->forceFill(['kirim_pada' => now()->addDay(), 'created_at' => now()->subDays(40)])->save();

        $this->artisan('ecowin:bersihkan-notifikasi')->assertSuccessful();

        $this->assertDatabaseMissing('notifikasi', ['id' => $tua->id]);
        $this->assertDatabaseHas('notifikasi', ['id' => $baru->id]);
        $this->assertDatabaseHas('notifikasi', ['id' => $lewat->id]);
        $this->assertDatabaseMissing('pengumuman', ['id' => $lamaTerkirim->id]);
        $this->assertDatabaseHas('pengumuman', ['id' => $baruTerkirim->id]);
        $this->assertDatabaseHas('pengumuman', ['id' => $terjadwal->id]); // yang belum terkirim tidak ikut terhapus
    }

    public function test_petugas_hanya_melihat_pengumuman_buatannya_sendiri_bukan_dari_admin(): void
    {
        $milikAdmin = new Pengumuman;
        $milikAdmin->forceFill(['dibuat_oleh' => $this->admin->id, 'bank_sampah_id' => $this->bank->id, 'judul' => 'Dari admin', 'isi' => 'x', 'kirim_pada' => now(), 'terkirim_at' => now()])->save();
        $milikPetugas = new Pengumuman;
        $milikPetugas->forceFill(['dibuat_oleh' => $this->petugas->id, 'bank_sampah_id' => $this->bank->id, 'judul' => 'Dari petugas', 'isi' => 'x', 'kirim_pada' => now(), 'terkirim_at' => now()])->save();
        $petugasLain = User::factory()->petugas($this->bank)->create();

        $lihat = Pengumuman::query()->visibleTo($this->petugas)->pluck('judul')->all();
        $this->assertSame(['Dari petugas'], $lihat);
        $this->assertFalse($this->petugas->can('view', $milikAdmin));
        $this->assertTrue($this->petugas->can('view', $milikPetugas));
        $this->assertSame([], Pengumuman::query()->visibleTo($petugasLain)->pluck('judul')->all(), 'petugas lain di bank sama juga tidak melihat punya rekannya');
        $this->assertCount(2, Pengumuman::query()->visibleTo($this->admin)->get(), 'admin melihat semua');
    }
}
