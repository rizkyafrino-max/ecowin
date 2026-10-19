<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Grafik bulanan memakai satu query berkelompok; hasilnya harus tepat per bulan dan tetap dibatasi hak akses. */
class GrafikBulananTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_by_month_with_zero_fill_and_respects_scope(): void
    {
        User::factory()->admin()->create();
        $bank = BankSampah::factory()->create();
        $petugas = User::factory()->petugas($bank)->create();
        $jenis = JenisSampah::factory()->create();
        HargaSampah::factory()->create(['jenis_sampah_id' => $jenis->id, 'harga_per_kg' => 1000]);
        $a = Nasabah::factory()->forBank($bank)->create();
        $b = Nasabah::factory()->forBank($bank)->create();

        $svc = app(TransaksiService::class);
        $t1 = $svc->catatAnorganik($petugas, $a, $jenis, 2);   // bulan ini
        $t2 = $svc->catatAnorganik($petugas, $a, $jenis, 3);   // 2 bulan lalu
        $t3 = $svc->catatAnorganik($petugas, $b, $jenis, 10);  // milik nasabah lain, bulan ini
        $t4 = $svc->catatAnorganik($petugas, $a, $jenis, 7);   // di luar jendela 6 bulan
        TransaksiAnorganik::query()->whereKey($t2->id)->update(['created_at' => now()->subMonths(2)->startOfMonth()->addDays(3)]);
        TransaksiAnorganik::query()->whereKey($t4->id)->update(['created_at' => now()->subMonths(8)]);

        $grafik = app(LaporanService::class)->grafikBulanan($a->user, 6);

        $this->assertCount(6, $grafik['labels']);
        $this->assertSame([0.0, 0.0, 0.0, 3.0, 0.0, 2.0], array_map('floatval', $grafik['berat']));
        $this->assertSame([0, 0, 0, 3000, 0, 2000], $grafik['nilai']);

        // Petugas melihat seluruh Bank Sampah miliknya (termasuk nasabah lain).
        $petugasGrafik = app(LaporanService::class)->grafikBulanan($petugas, 6);
        $this->assertSame(12.0, (float) end($petugasGrafik['berat']));

        // Satu query untuk seluruh grafik.
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(LaporanService::class)->grafikBulanan($a->user, 6);
        $this->assertCount(1, DB::getQueryLog());
    }
}
