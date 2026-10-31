<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Foto bukti laporan (mis. tangkapan layar bug). Dibuat seketat foto profil:
 *  - tipe diperiksa dari ISI berkas (bukan nama/ekstensi): hanya JPEG, PNG, WebP; SVG dan lainnya ditolak,
 *  - ukuran berkas dan jumlah piksel dibatasi sebelum didekode (mencegah "gambar bom"),
 *  - gambar didekode lalu DIGAMBAR ULANG menjadi JPEG (sisi terpanjang maksimal 1600 px, rasio dipertahankan agar tulisan
 *    pada tangkapan layar tetap terbaca): metadata (EXIF/GPS), komentar, dan muatan tersembunyi ikut terbuang,
 *  - nama berkas acak dan disimpan di disk privat; tidak pernah memakai nama dari pengguna.
 */
class FotoLaporanService
{
    public const MAKS_BYTE = 3 * 1024 * 1024;

    public const MAKS_PIKSEL = 25_000_000;

    public const MAKS_SISI = 1600;

    private const FOLDER = 'laporan';

    /** Memproses dan menyimpan foto dari byte mentah; mengembalikan jalur di disk privat. */
    public function simpan(string $isiMentah): string
    {
        $this->gagalBila(strlen($isiMentah) > self::MAKS_BYTE, 'Ukuran foto maksimal 3 MB.');
        $info = @getimagesizefromstring($isiMentah);
        $this->gagalBila($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true), 'Berkas harus berupa gambar JPEG, PNG, atau WebP.');
        [$lebar, $tinggi] = $info;
        $this->gagalBila($lebar < 1 || $tinggi < 1 || $lebar * $tinggi > self::MAKS_PIKSEL, 'Resolusi foto terlalu besar.');

        $gambar = @imagecreatefromstring($isiMentah);
        $this->gagalBila($gambar === false, 'Foto tidak dapat diproses. Coba foto lain.');

        $skala = min(1, self::MAKS_SISI / max($lebar, $tinggi));
        $hasil = imagecreatetruecolor(max(1, (int) round($lebar * $skala)), max(1, (int) round($tinggi * $skala)));
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255)); // latar putih untuk PNG/WebP transparan
        imagecopyresampled($hasil, $gambar, 0, 0, 0, 0, imagesx($hasil), imagesy($hasil), $lebar, $tinggi);

        ob_start();
        imagejpeg($hasil, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);
        imagedestroy($hasil);

        $jalur = self::FOLDER.'/'.now()->format('Y/m').'/'.Str::random(32).'.jpg';
        Storage::disk(config('ecowin.upload.disk'))->put($jalur, $jpeg, ['visibility' => 'private']);

        return $jalur;
    }

    public function hapus(?string $jalur): void
    {
        if ($jalur !== null && $this->jalurSah($jalur)) {
            Storage::disk(config('ecowin.upload.disk'))->delete($jalur);
        }
    }

    /** Hanya jalur buatan layanan ini yang pernah dibaca atau dihapus. */
    public function jalurSah(string $jalur): bool
    {
        return preg_match('#^'.self::FOLDER.'/\d{4}/\d{2}/[A-Za-z0-9]{32}\.jpg$#', $jalur) === 1;
    }

    private function gagalBila(bool $kondisi, string $pesan): void
    {
        if ($kondisi) {
            throw ValidationException::withMessages(['foto' => [$pesan]]);
        }
    }
}
