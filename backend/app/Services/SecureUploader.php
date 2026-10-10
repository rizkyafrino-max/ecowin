<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Upload gambar aman: validasi MIME dari isi file (bukan ekstensi), cek benar-benar
 * gambar, re-encode + kompresi (menghapus metadata/EXIF & payload tersembunyi),
 * nama file acak, disimpan di disk privat. Path disimpan di DB, bukan binary.
 */
class SecureUploader
{
    private const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function storeImage(UploadedFile $file, string $directory): string
    {
        $mime = $file->getMimeType();

        if (! in_array($mime, self::MIMES, true) || @getimagesize($file->getRealPath()) === false) {
            throw ValidationException::withMessages(['foto' => ['File harus berupa gambar JPG, PNG, atau WEBP.']]);
        }

        if ($file->getSize() > config('ecowin.upload.foto_max_kb') * 1024) {
            throw ValidationException::withMessages(['foto' => ['Ukuran foto terlalu besar.']]);
        }

        $disk = config('ecowin.upload.disk');
        $path = trim($directory, '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.jpg';
        $compressed = $this->compress($file->getRealPath(), $mime);

        Storage::disk($disk)->put($path, $compressed, ['visibility' => 'private']);

        return $path;
    }

    private function compress(string $realPath, string $mime): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return (string) file_get_contents($realPath);
        }

        $image = @imagecreatefromstring((string) file_get_contents($realPath));

        if ($image === false) {
            throw ValidationException::withMessages(['foto' => ['Gambar rusak atau tidak dapat dibaca.']]);
        }

        $maxSide = 1600;
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagejpeg($image, null, 80);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
