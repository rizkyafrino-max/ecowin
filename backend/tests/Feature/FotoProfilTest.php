<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\FotoProfilService;
use App\Services\PenghapusanAkunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ganti foto profil (semua role), avatar bawaan menurut jenis kelamin, dan keamanan unggahan. */
class FotoProfilTest extends TestCase
{
    use RefreshDatabase;

    private BankSampah $bank;

    private Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->bank = BankSampah::factory()->create();
        $this->nasabah = Nasabah::factory()->forBank($this->bank)->create();
    }

    private function gambar(string $format = 'jpg', int $w = 300, int $h = 200): string
    {
        $g = imagecreatetruecolor($w, $h);
        imagefill($g, 0, 0, imagecolorallocate($g, 30, 160, 110));
        imagefilledellipse($g, intdiv($w, 2), intdiv($h, 2), intdiv($w, 2), intdiv($h, 2), imagecolorallocate($g, 250, 220, 60));
        ob_start();
        match ($format) {
            'png' => imagepng($g),
            'webp' => imagewebp($g),
            default => imagejpeg($g, null, 90),
        };

        return (string) ob_get_clean();
    }

    /** Berkas unggahan sungguhan: tipe MIME dideteksi dari ISI (bukan dari nama), seperti di server nyata. */
    private function berkas(string $nama, string $isi): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'tes-foto');
        file_put_contents($jalur, $isi);

        return new UploadedFile($jalur, $nama, null, null, true);
    }

    private function unggah(User $user, string $isi, string $nama = 'foto.jpg')
    {
        return $this->actingAsApi($user)->post('/api/profile/foto', ['foto' => $this->berkas($nama, $isi)], ['Accept' => 'application/json']);
    }

    public function test_nasabah_mengganti_foto_dan_hasilnya_jpeg_persegi_384(): void
    {
        $user = $this->nasabah->user;
        $res = $this->unggah($user, $this->gambar('png', 500, 320), 'ktp-saya.png')->assertOk();

        $res->assertJsonPath('data.punya_foto', true);
        $kode = $user->fresh()->foto_profil;
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $kode);
        $this->assertSame(url('/foto/'.$kode), $res->json('data.avatar'));

        $img = $this->get('/foto/'.$kode)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $info = getimagesizefromstring($img->getContent());
        $this->assertSame([FotoProfilService::UKURAN, FotoProfilService::UKURAN, IMAGETYPE_JPEG], [$info[0], $info[1], $info[2]]);
        $this->assertStringNotContainsString('ktp-saya', (string) $kode, 'nama berkas dari pengguna tidak dipakai');
    }

    public function test_semua_role_boleh_mengganti_foto_sendiri(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->petugas($this->bank)->create()] as $u) {
            $this->unggah($u, $this->gambar('webp'), 'a.webp')->assertOk()->assertJsonPath('data.punya_foto', true);
            $this->assertNotNull($u->fresh()->foto_profil);
        }
    }

    public function test_foto_baru_menggantikan_dan_menghapus_berkas_lama_lalu_hapus_kembali_ke_bawaan(): void
    {
        $user = $this->nasabah->user;
        $user->forceFill(['jenis_kelamin' => 'P'])->save();

        $this->unggah($user, $this->gambar())->assertOk();
        $lama = $user->fresh()->foto_profil;
        $this->unggah($user, $this->gambar('png'))->assertOk();
        $baru = $user->fresh()->foto_profil;

        $this->assertNotSame($lama, $baru);
        Storage::disk('local')->assertMissing("profil/$lama.jpg");
        Storage::disk('local')->assertExists("profil/$baru.jpg");
        $this->get('/foto/'.$lama)->assertNotFound();

        $this->actingAsApi($user)->deleteJson('/api/profile/foto')->assertOk()
            ->assertJsonPath('data.punya_foto', false)->assertJsonPath('data.avatar', asset('images/avatar-perempuan.jpg'));
        Storage::disk('local')->assertMissing("profil/$baru.jpg");
    }

    public function test_avatar_bawaan_menurut_jenis_kelamin_dan_urutan_prioritas(): void
    {
        $user = $this->nasabah->user;
        $user->forceFill(['avatar' => 'https://lh3.googleusercontent.com/foto-google'])->save();

        $this->assertSame('https://lh3.googleusercontent.com/foto-google', $user->fresh()->avatarUrl(), 'tanpa jenis kelamin: foto Google');
        $this->assertSame(asset('images/avatar-laki.jpg'), tap($user)->forceFill(['jenis_kelamin' => 'L'])->avatarUrl());
        $this->assertSame(asset('images/avatar-perempuan.jpg'), tap($user)->forceFill(['jenis_kelamin' => 'P'])->avatarUrl());

        $this->unggah($user, $this->gambar())->assertOk();
        $this->assertStringStartsWith(url('/foto/'), $user->fresh()->avatarUrl(), 'foto unggahan menang atas avatar bawaan');
        $this->assertFileExists(public_path('images/avatar-laki.jpg'));
        $this->assertFileExists(public_path('images/avatar-perempuan.jpg'));
    }

    public function test_jenis_kelamin_diubah_lewat_profil_dan_hanya_L_atau_P(): void
    {
        $api = $this->actingAsApi($this->nasabah->user);
        $api->patchJson('/api/profile', ['jenis_kelamin' => 'P'])->assertOk()->assertJsonPath('data.jenis_kelamin', 'P');
        $this->assertSame('P', $this->nasabah->user->fresh()->jenis_kelamin);

        $this->actingAsApi($this->nasabah->user)->patchJson('/api/profile', ['jenis_kelamin' => 'X'])->assertUnprocessable();
        $this->actingAsApi($this->nasabah->user)->patchJson('/api/profile', ['jenis_kelamin' => null])->assertOk()->assertJsonPath('data.jenis_kelamin', null);
    }

    public function test_berkas_berbahaya_atau_bukan_gambar_ditolak(): void
    {
        $user = $this->nasabah->user;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->unggah($user, 'ini bukan gambar', 'a.jpg')->assertUnprocessable();
        $this->unggah($user, $svg, 'a.svg')->assertUnprocessable();
        $this->unggah($user, $svg, 'a.jpg')->assertUnprocessable();            // SVG menyamar sebagai jpg
        $this->unggah($user, "GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00;", 'a.gif')->assertUnprocessable(); // GIF tidak diizinkan
        $this->unggah($user, '<?php echo 1; ?>', 'skrip.php.jpg')->assertUnprocessable();
        $this->assertNull($user->fresh()->foto_profil);
    }

    public function test_ukuran_resolusi_dan_gambar_bom_ditolak(): void
    {
        $user = $this->nasabah->user;

        $this->unggah($user, $this->gambar('jpg', 40, 40), 'kecil.jpg')->assertUnprocessable()->assertJsonValidationErrors('foto');

        // Berkas > 3 MB
        $besar = $this->gambar('jpg', 100, 100).str_repeat("\0", 3 * 1024 * 1024 + 10);
        $this->unggah($user, $besar, 'besar.jpg')->assertUnprocessable();

        // Header PNG yang mengaku 8000x8000 (64 juta piksel) tanpa data: harus ditolak sebelum didekode
        $ihdr = pack('NNCCCCC', 8000, 8000, 8, 2, 0, 0, 0);
        $potongan = fn (string $tipe, string $data) => pack('N', strlen($data)).$tipe.$data.pack('N', crc32($tipe.$data));
        $bom = "\x89PNG\r\n\x1a\n".$potongan('IHDR', $ihdr).$potongan('IEND', '');
        $this->unggah($user, $bom, 'bom.png')->assertUnprocessable();
        $this->assertNull($user->fresh()->foto_profil);
    }

    public function test_metadata_dan_muatan_tersembunyi_dibuang_dari_hasil(): void
    {
        $jpeg = $this->gambar();
        $komentar = 'RAHASIA-GPS-LAT-6.2088 <?php echo 1; ?>';
        $dengan = substr($jpeg, 0, 2)."\xFF\xFE".pack('n', strlen($komentar) + 2).$komentar.substr($jpeg, 2).'<?php system("id"); ?>';
        $this->assertStringContainsString('RAHASIA-GPS', $dengan);

        $this->unggah($this->nasabah->user, $dengan)->assertOk();
        $isi = $this->get('/foto/'.$this->nasabah->user->fresh()->foto_profil)->getContent();

        $this->assertStringNotContainsString('RAHASIA-GPS', $isi);
        $this->assertStringNotContainsString('<?php', $isi);
        $this->assertStringNotContainsString('Exif', $isi);
    }

    public function test_akses_dan_kode_tidak_sah(): void
    {
        $this->post('/api/profile/foto', ['foto' => $this->berkas('a.jpg', $this->gambar())], ['Accept' => 'application/json'])->assertUnauthorized();
        $this->get('/foto/'.str_repeat('a', 32))->assertNotFound();
        $this->get('/foto/..%2F..%2F.env')->assertNotFound();
        $this->get('/foto/pendek')->assertNotFound();

        // Mengganti foto selalu milik sendiri: tidak ada parameter user yang bisa disusupkan
        $lain = Nasabah::factory()->forBank($this->bank)->create()->user;
        $this->actingAsApi($this->nasabah->user)->post('/api/profile/foto', ['foto' => $this->berkas('a.jpg', $this->gambar()), 'user_id' => $lain->id], ['Accept' => 'application/json'])->assertOk();
        $this->assertNull($lain->fresh()->foto_profil);
        $this->assertNotNull($this->nasabah->user->fresh()->foto_profil);
    }

    public function test_hapus_akun_membuang_berkas_foto_dan_jenis_kelamin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = $this->nasabah->user;
        $user->forceFill(['jenis_kelamin' => 'L'])->save();
        $this->unggah($user, $this->gambar())->assertOk();
        $kode = $user->fresh()->foto_profil;
        $this->nasabah->forceFill(['status' => 'nonaktif'])->save();
        $user->forceFill(['status' => User::STATUS_NONAKTIF])->save();

        app(PenghapusanAkunService::class)->hapusNasabah($admin, $this->nasabah->fresh());

        Storage::disk('local')->assertMissing("profil/$kode.jpg");
        $this->assertNull(app(FotoProfilService::class)->baca($kode), 'berkas tidak lagi bisa dibaca');
        $u = User::withTrashed()->find($user->id);
        $this->assertNull($u->foto_profil);
        $this->assertNull($u->jenis_kelamin);
    }

    public function test_pendaftaran_api_menyimpan_jenis_kelamin_opsional(): void
    {
        $data = ['jenis_kelamin' => 'P'];
        $this->assertSame('P', $this->nasabah->user->forceFill($data)->jenis_kelamin);
        $res = $this->actingAsApi($this->nasabah->user)->getJson('/api/auth/me')->assertOk();
        $this->assertArrayHasKey('jenis_kelamin', $res->json('data'));
        $this->assertArrayHasKey('punya_foto', $res->json('data'));
    }
}
