<?php

namespace App\Filament\Pages;

use App\Models\BankSampah;
use App\Models\User;
use App\Services\LaporanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
     * @var array{dari: ?string, sampai: ?string, bank: string}
     */
    public array $filter = ['dari' => null, 'sampai' => null, 'bank' => 'semua'];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaff() && $user->isActive();
    }

    public function mount(): void
    {
        $this->filter = ['dari' => now()->startOfMonth()->toDateString(), 'sampai' => now()->toDateString(), 'bank' => 'semua'];
    }

    public function filterForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('filter')
            ->components([
                Section::make()->schema([
                    // Admin memilih Bank Sampah (atau semua); Petugas hanya melihat Bank Sampah yang dikelolanya.
                    Select::make('bank')->label('Bank Sampah')->options(fn (): array => ['semua' => 'Semua Bank Sampah'] + BankSampah::query()->orderBy('nama_bank_sampah')->get()->mapWithKeys(fn (BankSampah $b): array => [(string) $b->id => $b->nama_bank_sampah.' (RT '.$b->rt.' / RW '.$b->rw.')'])->all())
                        ->default('semua')->selectablePlaceholder(false)->live()->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)->columnSpanFull(),
                    TextInput::make('bank_info')->label('Bank Sampah')->disabled()->dehydrated(false)
                        ->formatStateUsing(fn (): ?string => auth()->user()?->bankSampah?->nama_bank_sampah)
                        ->visible(fn (): bool => auth()->user()?->isPetugas() ?? false)->columnSpanFull(),
                    DatePicker::make('dari')->label('Dari tanggal')->live(),
                    DatePicker::make('sampai')->label('Sampai tanggal')->live()->afterOrEqual('dari'),
                ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make(array_map(fn (string $jenis, string $label): Action => Action::make('ekspor_'.$jenis)->label($label)->icon('heroicon-o-table-cells')
                ->url(fn (): string => route('admin.laporan.ekspor', ['jenis' => $jenis] + array_filter([
                    'dari' => $this->filter['dari'] ?? null,
                    'sampai' => $this->filter['sampai'] ?? null,
                    'bank' => (auth()->user()?->isAdmin() ?? false) ? ($this->filter['bank'] ?? 'semua') : null,
                ]))), ['anorganik', 'organik', 'penarikan', 'rekap'], ['Setoran anorganik (CSV)', 'Setoran organik (CSV)', 'Penarikan saldo (CSV)', 'Rekap per Bank Sampah (CSV)']))
                ->label('Ekspor CSV')->icon('heroicon-o-arrow-down-tray')->button()->color('gray'),
            Action::make('cetak')->label('Cetak laporan')->icon('heroicon-o-printer')
                ->url(fn (): string => route('admin.laporan.cetak', array_filter([
                    'dari' => $this->filter['dari'] ?? null,
                    'sampai' => $this->filter['sampai'] ?? null,
                    'bank' => (auth()->user()?->isAdmin() ?? false) ? ($this->filter['bank'] ?? 'semua') : null,
                    'print' => 1,
                ])))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        $dari = $this->filter['dari'] ? Carbon::parse($this->filter['dari']) : null;
        $sampai = $this->filter['sampai'] ? Carbon::parse($this->filter['sampai']) : null;

        $bank = ($this->filter['bank'] ?? 'semua') !== 'semua' ? (int) $this->filter['bank'] : null;

        return ['r' => app(LaporanService::class)->ringkasan(auth()->user(), $dari, $sampai, $bank)];
    }
}
