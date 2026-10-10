<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/** Satu menu "Organik": setoran, aktivitas (Biopori/BioporiPrint), lokasi & panen, dan peta. */
class Organik extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Organik';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'organik';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
