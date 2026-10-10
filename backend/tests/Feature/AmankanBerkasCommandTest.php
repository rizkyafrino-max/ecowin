<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AmankanBerkasCommandTest extends TestCase
{
    public function test_sensitive_files_are_moved_from_public_to_private_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('ktp-kk/ktp.jpg', 'data');
        Storage::disk('public')->put('titik-biopori/lokasi.jpg', 'boleh publik');

        $this->artisan('ecowin:amankan-berkas')->assertSuccessful();

        Storage::disk('public')->assertMissing('ktp-kk/ktp.jpg');
        Storage::disk('local')->assertExists('ktp-kk/ktp.jpg');
        Storage::disk('public')->assertExists('titik-biopori/lokasi.jpg');
    }
}
