<?php

namespace App\Filament\Resources\JadwalPengumpulans;

use App\Filament\Resources\JadwalPengumpulans\Pages\CreateJadwalPengumpulan;
use App\Filament\Resources\JadwalPengumpulans\Pages\EditJadwalPengumpulan;
use App\Filament\Resources\JadwalPengumpulans\Pages\ListJadwalPengumpulans;
use App\Models\BankSampah;
use App\Models\JadwalPengumpulan;
use App\Services\AuditLogger;
use App\Services\JadwalPengumpulanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Jadwal pengumpulan sampah per bank sampah (khusus Admin). Pada hari jadwal, nasabah bank sampah itu otomatis menerima
 * pengingat sebelum kegiatan dimulai. Jadwal bisa diedit kapan saja; "Kabarkan perubahan" memberi tahu nasabah seketika.
 */
class JadwalPengumpulanResource extends Resource
{
    protected static ?string $model = JadwalPengumpulan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Jadwal Pengumpulan';

    protected static ?string $modelLabel = 'jadwal pengumpulan';

    protected static ?string $pluralModelLabel = 'jadwal pengumpulan';

    protected static ?string $slug = 'jadwal-pengumpulan';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('bankSampah');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank sampah dan hari')->schema([
                Select::make('bank_sampah_id')->label('Bank sampah')->required()->searchable()
                    ->options(fn (): array => BankSampah::query()->orderBy('nama_bank_sampah')->get()->mapWithKeys(fn (BankSampah $b): array => [$b->id => $b->label])->all()),
                CheckboxList::make('hari')->label('Hari pengumpulan')->required()->columns(3)->bulkToggleable(false)
                    ->options(JadwalPengumpulan::HARI)
                    ->minItems(1)->validationMessages(['min' => 'Pilih minimal satu hari.']),
            ]),
            Section::make('Waktu dan tempat')->schema([
                TimePicker::make('jam_mulai')->label('Jam mulai')->required()->seconds(false),
                TimePicker::make('jam_selesai')->label('Jam selesai (opsional)')->seconds(false)->after('jam_mulai')->validationMessages(['after' => 'Jam selesai harus setelah jam mulai.']),
                TextInput::make('lokasi')->label('Lokasi / titik kumpul')->maxLength(120)->placeholder('Pos RW 05'),
                Textarea::make('catatan')->label('Catatan untuk nasabah (opsional)')->maxLength(200)->rows(2)->placeholder('Mohon sampah sudah dipilah dan dibawa.'),
            ])->columns(2),
            Section::make('Pengingat otomatis')->schema([
                Select::make('pengingat_menit')->label('Kirim pengingat')->options(JadwalPengumpulan::PENGINGAT)->default(120)->required()->native(false)
                    ->helperText('Pengingat dikirim ke nasabah aktif bank sampah ini sekali pada hari jadwal. Server harus menjalankan penjadwal Laravel.'),
                Toggle::make('aktif')->label('Aktif')->default(true)->helperText('Matikan untuk menghentikan pengingat sementara (mis. libur).'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->groups([
                Group::make('bank_sampah_id')->label('Bank sampah')->collapsible()
                    ->getTitleFromRecordUsing(fn (JadwalPengumpulan $r): string => $r->bankSampah?->label ?? '-'),
            ])
            ->defaultGroup('bank_sampah_id')
            ->columns([
                TextColumn::make('hari')->label('Hari')->badge()->state(fn (JadwalPengumpulan $r): array => array_filter(explode(', ', $r->namaHari()))),
                TextColumn::make('jam')->label('Jam')->state(fn (JadwalPengumpulan $r): string => $r->rentangJam()),
                TextColumn::make('lokasi')->placeholder('-')->wrap(),
                TextColumn::make('pengingat_menit')->label('Pengingat')->formatStateUsing(fn ($state): string => JadwalPengumpulan::PENGINGAT[(int) $state] ?? "{$state} menit sebelumnya"),
                ToggleColumn::make('aktif')->label('Aktif'),
                TextColumn::make('terakhir_kirim_tanggal')->label('Terakhir dikirim')->date('d M Y')->placeholder('Belum pernah'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('kabarkan')->label('Kabarkan perubahan')->icon('heroicon-o-megaphone')
                    ->requiresConfirmation()
                    ->modalHeading('Kabarkan jadwal ke nasabah sekarang?')
                    ->modalDescription(fn (JadwalPengumpulan $r): string => 'Nasabah aktif '.($r->bankSampah?->nama_bank_sampah ?? 'bank sampah ini').' akan menerima notifikasi "Perubahan jadwal pengumpulan sampah" berisi jadwal terbaru. Tidak bisa ditarik.')
                    ->modalSubmitActionLabel('Ya, kabarkan')
                    ->action(function (JadwalPengumpulan $r): void {
                        app(JadwalPengumpulanService::class)->umumkanPerubahan($r, auth()->user());
                        Notification::make()->success()->title('Perubahan jadwal dikabarkan ke nasabah')->send();
                    }),
                DeleteAction::make()->before(fn (JadwalPengumpulan $r) => app(AuditLogger::class)->log('hapus_jadwal_pengumpulan', $r, $r->attributesToArray(), null)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalPengumpulans::route('/'),
            'create' => CreateJadwalPengumpulan::route('/create'),
            'edit' => EditJadwalPengumpulan::route('/{record}/edit'),
        ];
    }
}
