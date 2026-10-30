<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Foto profil unggahan pengguna. Aman karena:
 *  - tipe diperiksa dari ISI berkas (bukan nama/ekstensi): hanya JPEG, PNG, WebP; SVG dan lainnya ditolak,
 *  - ukuran berkas dan jumlah piksel dibatasi sebelum didekode (mencegah "gambar bom" yang menghabiskan memori),
 *  - gambar didekode lalu DIGAMBAR ULANG menjadi JPEG 384x384 (potong persegi tengah): semua metadata (EXIF/GPS),
 *    komentar, dan muatan tersembunyi di dalam berkas asli ikut terbuang,
 *  - nama berkas acak (kode 32 karakter) dan disimpan di disk privat; tidak pernah memakai nama dari pengguna.
 */
class FotoProfilService
{
    public const UKURAN = 384;

    public const MAKS_BYTE = 3 * 1024 * 1024;

    public const MIN_SISI = 64;

    public const MAKS_PIKSEL = 25_000_000;

    private const FOLDER = 'profil';

    /** Memproses dan menyimpan foto dari byte mentah; mengganti foto sebelumnya. */
    public function simpan(User $user, string $isiMentah): void
    {
        $this->gagalBila(strlen($isiMentah) > self::MAKS_BYTE, 'Ukuran foto maksimal 3 MB.');
        $info = @getimagesizefromstring($isiMentah);
        $this->gagalBila($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true), 'Berkas harus berupa gambar JPEG, PNG, atau WebP.');
        [$lebar, $tinggi] = $info;
        $this->gagalBila($lebar < self::MIN_SISI || $tinggi < self::MIN_SISI, 'Foto terlalu kecil (minimal 64 x 64 piksel).');
        $this->gagalBila($lebar * $tinggi > self::MAKS_PIKSEL, 'Resolusi foto terlalu besar.');

        $gambar = @imagecreatefromstring($isiMentah);
        $this->gagalBila($gambar === false, 'Foto tidak dapat diproses. Coba foto lain.');

        if ($info[2] === IMAGETYPE_JPEG) {
            $gambar = $this->putarSesuaiExif($gambar, $isiMentah);
        }
        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);

        $sisi = min($lebar, $tinggi);
        $hasil = imagecreatetruecolor(self::UKURAN, self::UKURAN);
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255)); // latar putih untuk PNG/WebP transparan
        imagecopyresampled($hasil, $gambar, 0, 0, intdiv($lebar - $sisi, 2), intdiv($tinggi - $sisi, 2), self::UKURAN, self::UKURAN, $sisi, $sisi);

        ob_start();
        imagejpeg($hasil, null, 86);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);
        imagedestroy($hasil);

        $kode = Str::random(32);
        Storage::disk('local')->put(self::FOLDER.'/'.$kode.'.jpg', $jpeg);

        $lama = $user->foto_profil;
        $user->forceFill(['foto_profil' => $kode])->save();
        $this->hapusBerkas($lama);
    }

    /** Kembali ke avatar bawaan. */
    public function hapus(User $user): void
    {
        $lama = $user->foto_profil;
        if ($lama === null) {
            return;
        }
        $user->forceFill(['foto_profil' => null])->save();
        $this->hapusBerkas($lama);
    }

    /** Isi JPEG untuk kode tertentu, atau null bila tidak ada. */
    public function baca(string $kode): ?string
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $kode)) {
            return null;
        }
        $jalur = self::FOLDER.'/'.$kode.'.jpg';

        return Storage::disk('local')->exists($jalur) ? Storage::disk('local')->get($jalur) : null;
    }

    private function hapusBerkas(?string $kode): void
    {
        if ($kode !== null && preg_match('/^[A-Za-z0-9]{32}$/', $kode)) {
            Storage::disk('local')->delete(self::FOLDER.'/'.$kode.'.jpg');
        }
    }

    private function putarSesuaiExif(\GdImage $gambar, string $isi): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $gambar;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($isi));
        $arah = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $sudut = [3 => 180, 6 => -90, 8 => 90][$arah] ?? 0;
        if ($sudut === 0) {
            return $gambar;
        }
        $putar = imagerotate($gambar, $sudut, 0);

        return $putar instanceof \GdImage ? $putar : $gambar;
    }

    private function gagalBila(bool $kondisi, string $pesan): void
    {
        if ($kondisi) {
            throw ValidationException::withMessages(['foto' => [$pesan]]);
        }
    }
}
