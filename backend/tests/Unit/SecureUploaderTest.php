<?php

namespace Tests\Unit;

use App\Services\SecureUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Pengunggah gambar bersama (foto bukti biopori, dokumentasi setoran): isi diperiksa, dimensi dibatasi sebelum didekode,
 * dan hasilnya digambar ulang menjadi JPEG di disk privat.
 */
class SecureUploaderTest extends TestCase
{
    /** PNG yang hanya berisi kepala dengan dimensi yang dinyatakan: dipakai untuk menguji penolakan "gambar bom". */
    private function pngKepala(int $lebar, int $tinggi): string
    {
        $ihdr = pack('NNCCCCC', $lebar, $tinggi, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
    }

    private function pngNyata(int $lebar, int $tinggi): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        ob_start();
        imagepng($gambar);
        imagedestroy($gambar);

        return (string) ob_get_clean();
    }

    public function test_a_real_image_is_redrawn_as_jpeg_in_the_private_disk(): void
    {
        Storage::fake(config('ecowin.upload.disk'));

        $path = app(SecureUploader::class)->storeImage(UploadedFile::fake()->createWithContent('foto.png', $this->pngNyata(400, 300)), 'uji');

        Storage::disk(config('ecowin.upload.disk'))->assertExists($path);
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring(Storage::disk(config('ecowin.upload.disk'))->get($path))[2]);
    }

    public function test_oversized_dimensions_are_rejected_before_decoding(): void
    {
        Storage::fake(config('ecowin.upload.disk'));
        $maks = (int) config('ecowin.upload.foto_max_dimensi');

        foreach ([[$maks + 1, 100], [100, $maks + 1], [10_000, 10_000]] as [$w, $h]) {
            try {
                app(SecureUploader::class)->storeImage(UploadedFile::fake()->createWithContent('bom.png', $this->pngKepala($w, $h)), 'uji');
                $this->fail("Gambar {$w}x{$h} seharusnya ditolak.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('foto', $e->errors());
            }
        }

        $this->assertSame([], Storage::disk(config('ecowin.upload.disk'))->allFiles());
    }

    public function test_non_images_are_rejected(): void
    {
        Storage::fake(config('ecowin.upload.disk'));

        $this->expectException(ValidationException::class);
        app(SecureUploader::class)->storeImage(UploadedFile::fake()->createWithContent('foto.png', '<?php echo 1;'), 'uji');
    }
}
