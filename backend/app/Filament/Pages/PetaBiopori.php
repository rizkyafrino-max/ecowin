<?php

namespace App\Filament\Pages;

use App\Models\TitikBiopori as TitikBioporiModel;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PetaBiopori extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Peta';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Peta Biopori (BioporiPrint)';

    protected static ?string $cluster = \App\Filament\Clusters\Organik::class;

    protected string $view = 'filament.pages.peta-biopori';

    public function getViewData(): array
    {
        $query = TitikBioporiModel::query()->with(['nasabah:id,nama', 'bankSampah:id,nama_bank_sampah,rt,rw'])
            ->whereNotNull('latitude')->whereNotNull('longitude');
        $query->visibleTo(auth()->user());

        $points = $query->latest()->get()->map(fn (TitikBioporiModel $point): array => [
            'id' => $point->id,
            'nama' => $point->nasabah?->nama ?? 'Nasabah tidak diketahui',
            'bank' => $point->bankSampah?->nama_bank_sampah ?? 'Bank sampah tidak diketahui',
            'alamat' => $point->alamat_rt_rw,
            'keterangan' => $point->deskripsi_lokasi,
            'tanggal' => $point->tanggal_tanam?->format('d M Y'),
            'pipa' => $point->jumlah_pipa,
            'status' => $point->status,
            'lat' => (float) $point->latitude,
            'lng' => (float) $point->longitude,
        ]);

        return ['points' => $points, 'activeCount' => $points->where('status', 'aktif')->count(), 'totalPipes' => $points->sum('pipa')];
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }
}
