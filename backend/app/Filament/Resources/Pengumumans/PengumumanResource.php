<?php

namespace App\Filament\Resources\Pengumumans;

use App\Filament\Resources\Pengumumans\Pages\CreatePengumuman;
use App\Filament\Resources\Pengumumans\Pages\ListPengumumans;
use App\Models\BankSampah;
use App\Models\Pengumuman;
use App\Services\AuditLogger;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Pengumuman untuk nasabah (mis. jadwal pengumpulan sampah). Dikirim sebagai notifikasi dalam aplikasi
 * (web dan Android). Petugas hanya bisa mengirim ke bank sampahnya; Admin bisa ke satu atau semua bank sampah.
 */
class PengumumanResource extends Resource
{
    protected static ?string $model = Pengumuman::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Pengumuman';

    protected static ?string $modelLabel = 'pengumuman';

    protected static ?string $pluralModelLabel = 'pengumuman';

    protected static ?string $slug = 'pengumuman';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        $admin = auth()->user()?->isAdmin() ?? false;

        return $schema->components([
            Section::make('Isi pengumuman')->description('Dikirim sebagai notifikasi ke nasabah aktif. Teks biasa saja; setelah terkirim tidak bisa ditarik kembali.')->schema([
                TextInput::make('judul')->label('Judul')->required()->maxLength(120)->placeholder('Jadwal pengumpulan sampah hari ini'),
                Textarea::make('isi')->label('Isi')->required()->maxLength(300)->rows(4)->placeholder('Contoh: Pengumpulan sampah anorganik hari ini pukul 08.00-10.00 di Pos RW 05. Mohon dipilah dan dibawa.'),
            ]),
            Section::make('Tujuan dan waktu')->schema([
                Select::make('bank_sampah_id')->label('Bank sampah tujuan')
                    ->options(fn (): array => BankSampah::query()->orderBy('nama_bank_sampah')->get()->mapWithKeys(fn (BankSampah $b): array => [$b->id => $b->label])->all())
                    ->placeholder('Semua bank sampah')->searchable()
                    ->visible($admin)
                    ->helperText('Kosongkan untuk mengirim ke nasabah semua bank sampah.'),
                DateTimePicker::make('kirim_pada')->label('Jadwal kirim')->seconds(false)->minDate(now()->startOfMinute())
                    ->helperText('Kosongkan untuk mengirim sekarang. Pengumuman terjadwal dikirim otomatis (server harus menjalankan penjadwal) dan bisa dibatalkan sebelum waktunya.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pembuat', 'bankSampah']))
            ->columns([
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                TextColumn::make('judul')->searchable()->limit(40),
                TextColumn::make('isi')->limit(70)->wrap()->toggleable(),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Tujuan')->placeholder('Semua bank sampah'),
                TextColumn::make('pembuat.nama')->label('Pengirim'),
                TextColumn::make('status')->label('Status')->badge()
                    ->state(fn (Pengumuman $p): string => $p->sudahTerkirim() ? 'Terkirim' : 'Terjadwal')
                    ->color(fn (string $state): string => $state === 'Terkirim' ? 'success' : 'warning'),
                TextColumn::make('kirim_pada')->label('Waktu kirim')->dateTime('d M Y H:i'),
                TextColumn::make('jumlah_penerima')->label('Penerima')->numeric()->placeholder('-')
                    ->state(fn (Pengumuman $p): ?int => $p->sudahTerkirim() ? $p->jumlah_penerima : null),
            ])
            ->recordActions([
                DeleteAction::make()->label('Batalkan')->icon('heroicon-o-x-mark')->modalHeading('Batalkan pengumuman terjadwal?')
                    ->visible(fn (Pengumuman $p): bool => auth()->user()?->can('delete', $p) ?? false)
                    ->before(function (Pengumuman $p): void {
                        app(AuditLogger::class)->log('batal_pengumuman', $p, ['judul' => $p->judul, 'kirim_pada' => (string) $p->kirim_pada], null);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengumumans::route('/'),
            'create' => CreatePengumuman::route('/create'),
        ];
    }
}
