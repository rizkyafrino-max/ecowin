<?php

namespace App\Filament\Resources\Hargas\Pages;

use App\Filament\Resources\Hargas\HargaSampahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHargaSampah extends ListRecords
{
    protected static string $resource = HargaSampahResource::class;

    protected function getHeaderActions(): array
    {
        return auth()->user()->isAdmin() ? [CreateAction::make()] : [];
    }
}
