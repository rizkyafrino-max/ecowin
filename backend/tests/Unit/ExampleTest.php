<?php

namespace Tests\Unit;

use App\Services\KomposCalculator;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_kompos_estimate_uses_configurable_ratio(): void
    {
        config(['ecowin.kompos.rasio_default' => 0.5, 'ecowin.kompos.rasio_per_metode.maggot' => 0.3]);

        $this->assertSame(5.0, app(KomposCalculator::class)->estimasi(10));
        $this->assertSame(3.0, app(KomposCalculator::class)->estimasi(10, 'maggot'));
    }
}
