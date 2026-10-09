<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\LaporanService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Laporan Anorganik, Organik, Keuangan.
 * Admin: seluruh Bank Sampah. Petugas: hanya Bank Sampah miliknya (via scope di LaporanService).
 */
class Laporan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected string $view = 'filament.pages.laporan';

    /**
     * @var array{dari: ?string, sampai: ?string}
     */
    public array $filter = ['dari' => null, 'sampai' => null];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaff() && $user->isActive();
    }

    public function mount(): void
    {
        $this->filter = ['dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->toDateString()];
    }

    public function filterForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('filter')
            ->components([
                Section::make()->schema([
                    DatePicker::make('dari')->label('Dari tanggal')->live(),
                    DatePicker::make('sampai')->label('Sampai tanggal')->live()->afterOrEqual('dari'),
                ])->columns(2),
            ]);
    }

    protected function getViewData(): array
    {
        $dari = $this->filter['dari'] ? Carbon::parse($this->filter['dari']) : null;
        $sampai = $this->filter['sampai'] ? Carbon::parse($this->filter['sampai']) : null;

        return ['r' => app(LaporanService::class)->ringkasan(auth()->user(), $dari, $sampai)];
    }
}
