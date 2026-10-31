<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\LaporanKendala;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\FotoLaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lapor bug dari aplikasi (kategori "bug" di laporan kendala): pelapor selalu dari akun login, input dibatasi, dan ada pembatas laju.
 */
class LaporBugTest extends TestCase
{
    use RefreshDatabase;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $bank = BankSampah::factory()->create();
        $this->nasabah = Nasabah::factory()->forBank($bank)->create();
    }

    public function test_nasabah_reports_a_bug_and_reporter_comes_from_the_account(): void
    {
        $lain = Nasabah::factory()->forBank(BankSampah::factory()->create())->create();

        $this->actingAsApi($this->nasabah->user)
            ->postJson('/api/laporan-kendala', [
                'kategori' => 'bug',
                'deskripsi' => "Layar putih saat Tarik Saldo\n\n— Info otomatis —\nEcoWin 2.0 • Android 14",
                'status' => 'disetujui', 'bank_sampah_id' => $lain->bank_sampah_id,
                'dilaporkan_oleh_nasabah_id' => $lain->id, 'dilaporkan_oleh_user_id' => $lain->user_id,
            ])
            ->assertCreated();

        $laporan = LaporanKendala::query()->firstOrFail();
        $this->assertSame('bug', $laporan->kategori);
        $this->assertSame('menunggu', $laporan->status);
        $this->assertSame($this->nasabah->id, $laporan->dilaporkan_oleh_nasabah_id);
        $this->assertSame($this->nasabah->user_id, $laporan->dilaporkan_oleh_user_id);
        // Bank sampah diambil dari akun yang login, bukan dari nilai yang disuntikkan lewat input.
        $this->assertSame($this->nasabah->user->bank_sampah_id, $laporan->bank_sampah_id);
        $this->assertNotSame($lain->bank_sampah_id, $laporan->bank_sampah_id);
    }

    public function test_invalid_reports_are_rejected(): void
    {
        $this->actingAsApi($this->nasabah->user);

        $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => ''])->assertStatus(422)->assertJsonValidationErrors('deskripsi');
        $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => str_repeat('x', 2001)])->assertStatus(422)->assertJsonValidationErrors('deskripsi');
        $this->postJson('/api/laporan-kendala', ['kategori' => 'lainnya', 'deskripsi' => 'Ada masalah'])->assertStatus(422)->assertJsonValidationErrors('kategori');

        $this->assertDatabaseCount('laporan_kendala', 0);
    }

    public function test_guests_cannot_report(): void
    {
        $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Ada masalah di aplikasi'])->assertUnauthorized();
    }

    public function test_reports_are_rate_limited(): void
    {
        $this->actingAsApi($this->nasabah->user);

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => "Masalah nomor $i di aplikasi"])->assertCreated();
        }

        $this->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Masalah keenam di aplikasi'])->assertStatus(429);
        $this->assertDatabaseCount('laporan_kendala', 5);
    }

    public function test_nasabah_only_sees_own_reports(): void
    {
        $lain = Nasabah::factory()->forBank($this->nasabah->bank_sampah_id ? BankSampah::find($this->nasabah->bank_sampah_id) : BankSampah::factory()->create())->create();

        $this->actingAsApi($lain->user)->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Laporan milik nasabah lain'])->assertCreated();
        $this->actingAsApi($this->nasabah->user)->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Laporan milik saya sendiri'])->assertCreated();

        $this->actingAsApi($this->nasabah->user)
            ->getJson('/api/laporan-kendala?kategori=bug')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.deskripsi', 'Laporan milik saya sendiri');
    }

    private function pngBytes(int $lebar, int $tinggi): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 16, 185, 129));
        ob_start();
        imagepng($gambar);
        imagedestroy($gambar);

        return (string) ob_get_clean();
    }

    /** PNG yang hanya berisi kepala dengan dimensi raksasa: dipakai untuk menguji penolakan "gambar bom" sebelum didekode. */
    private function pngBom(int $lebar, int $tinggi): string
    {
        $ihdr = pack('NNCCCCC', $lebar, $tinggi, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
    }

    private function kirimDenganFoto(User $user, UploadedFile $foto, string $deskripsi = 'Layar putih saat Tarik Saldo'): \Illuminate\Testing\TestResponse
    {
        return $this->actingAsApi($user)->post('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => $deskripsi, 'foto' => $foto], ['Accept' => 'application/json']);
    }

    public function test_photo_evidence_is_redrawn_stored_privately_and_path_is_not_exposed(): void
    {
        Storage::fake(config('ecowin.upload.disk'));

        $respons = $this->kirimDenganFoto($this->nasabah->user, UploadedFile::fake()->createWithContent('layar.png', $this->pngBytes(3000, 1500)))
            ->assertCreated()->assertJsonPath('punya_foto', true);

        $this->assertStringNotContainsString('foto_bukti_path', $respons->getContent());

        $laporan = LaporanKendala::query()->firstOrFail();
        $this->assertMatchesRegularExpression('#^laporan/\d{4}/\d{2}/[A-Za-z0-9]{32}\.jpg$#', $laporan->foto_bukti_path);
        Storage::disk(config('ecowin.upload.disk'))->assertExists($laporan->foto_bukti_path);

        // Digambar ulang menjadi JPEG dengan sisi terpanjang 1600 dan rasio terjaga.
        $info = getimagesizefromstring(Storage::disk(config('ecowin.upload.disk'))->get($laporan->foto_bukti_path));
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        $this->assertSame([1600, 800], [$info[0], $info[1]]);
    }

    public function test_report_without_photo_still_works(): void
    {
        $this->actingAsApi($this->nasabah->user)->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Tanpa foto bukti'])
            ->assertCreated()->assertJsonPath('punya_foto', false);
        $this->assertNull(LaporanKendala::query()->firstOrFail()->foto_bukti_path);
    }

    public function test_unsafe_photos_are_rejected_and_no_report_is_created(): void
    {
        Storage::fake(config('ecowin.upload.disk'));
        $this->actingAsApi($this->nasabah->user);

        // Skrip berkedok gambar, SVG, dan berkas terlalu besar
        foreach ([
            UploadedFile::fake()->createWithContent('layar.png', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('layar.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->create('besar.jpg', 4000, 'image/jpeg'),
        ] as $berkas) {
            $this->kirimDenganFoto($this->nasabah->user, $berkas)->assertStatus(422)->assertJsonValidationErrors('foto');
        }

        // Gambar bom: kepala PNG menyatakan 10.000 x 10.000 piksel
        $this->kirimDenganFoto($this->nasabah->user, UploadedFile::fake()->createWithContent('bom.png', $this->pngBom(10_000, 10_000)))
            ->assertStatus(422)->assertJsonValidationErrors('foto');

        $this->assertDatabaseCount('laporan_kendala', 0);
        $this->assertSame([], Storage::disk(config('ecowin.upload.disk'))->allFiles());
    }

    public function test_service_rejects_a_bomb_before_decoding(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(FotoLaporanService::class)->simpan($this->pngBom(10_000, 10_000));
    }

    public function test_only_staff_who_may_see_the_report_can_open_the_photo(): void
    {
        Storage::fake(config('ecowin.upload.disk'));
        $this->kirimDenganFoto($this->nasabah->user, UploadedFile::fake()->createWithContent('layar.png', $this->pngBytes(400, 300)))->assertCreated();
        $laporan = LaporanKendala::query()->firstOrFail();
        $url = route('admin.berkas.laporan', $laporan);

        $this->actingAs(User::factory()->admin()->create(), 'web')->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->petugas(BankSampah::find($laporan->bank_sampah_id))->create(), 'web')->get($url)->assertOk();
        $this->actingAs(User::factory()->petugas(BankSampah::factory()->create())->create(), 'web')->get($url)->assertForbidden();
        $this->actingAs($this->nasabah->user, 'web')->get($url)->assertForbidden();

    }

    public function test_guests_are_redirected_from_the_photo_route(): void
    {
        $this->get('/admin/berkas/laporan/1')->assertRedirect();
    }

    public function test_photo_route_is_404_for_a_report_without_photo(): void
    {
        $this->actingAsApi($this->nasabah->user)->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => 'Tanpa foto bukti'])->assertCreated();

        $this->actingAs(User::factory()->admin()->create(), 'web')->get(route('admin.berkas.laporan', LaporanKendala::query()->firstOrFail()))->assertNotFound();
    }

    public function test_admin_dashboard_popup_announces_new_bug_reports_but_petugas_does_not_see_them(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAsApi($this->nasabah->user)->postJson('/api/laporan-kendala', ['kategori' => 'bug', 'deskripsi' => "Layar putih saat Tarik Saldo\n\n— Info otomatis —"])->assertCreated();
        $this->actingAsApi($this->nasabah->user)->postJson('/api/laporan-kendala', ['kategori' => 'pencatatan', 'deskripsi' => 'Salah catat berat'])->assertCreated();

        $this->actingAs($admin, 'web')->get('/admin')->assertOk()
            ->assertSee('eco-popup', false)->assertSee('Ada 1 tugas menunggu')->assertSee('Laporan bug')->assertSee('Layar putih saat Tarik Saldo');

        $petugas = User::factory()->petugas(BankSampah::find($this->nasabah->bank_sampah_id))->create();
        $this->actingAs($petugas, 'web')->get('/admin')->assertOk()->assertDontSee('eco-popup-title', false);

        LaporanKendala::query()->where('kategori', 'bug')->update(['status' => 'disetujui']);
        $this->actingAs($admin, 'web')->get('/admin')->assertOk()->assertDontSee('eco-popup-title', false);
    }
}
