<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_every_feature_and_is_idempotent(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThanOrEqual(20, DB::table('transaksi_anorganik')->count());
        $this->assertGreaterThan(0, DB::table('transaksi_organik')->count());
        $this->assertGreaterThan(0, DB::table('aktivitas_biopori')->where('metode_pengolahan', 'bioporiprint')->count());
        $this->assertSame(3, DB::table('aktivitas_biopori')->distinct()->pluck('status')->count());
        $this->assertGreaterThan(0, DB::table('penarikan_saldo')->count());

        $jumlah = DB::table('transaksi_anorganik')->count();
        $this->seed(DemoDataSeeder::class);
        $this->assertSame($jumlah, DB::table('transaksi_anorganik')->count());
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';
        (new DemoDataSeeder)->run();

        $this->assertSame(0, DB::table('transaksi_anorganik')->count());
    }
}
