<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\LaporanService;
use Filament\Widgets\ChartWidget;

class TransaksiChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Setoran anorganik 6 bulan terakhir';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isStaff();
    }

    protected function getData(): array
    {
        $data = app(LaporanService::class)->grafikBulanan(auth()->user());
        $color = auth()->user()->isAdmin() ? '#4F46E5' : '#059669';

        return [
            'datasets' => [[
                'label' => 'Berat (kg)',
                'data' => $data['berat'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderRadius' => 6,
            ]],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
