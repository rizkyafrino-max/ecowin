<?php

namespace App\Filament\Resources\Nasabahs;

use App\Filament\Concerns\ScopesToCurrentUser;
use App\Filament\Resources\Nasabahs\Pages\CreateNasabah;
use App\Filament\Resources\Nasabahs\Pages\EditNasabah;
use App\Filament\Resources\Nasabahs\Pages\ListNasabahs;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class NasabahResource extends Resource
{
    use ScopesToCurrentUser;

    protected static ?string $model = Nasabah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Nasabah';

    protected static ?string $modelLabel = 'nasabah';

    protected static ?string $pluralModelLabel = 'nasabah';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $isAdmin = fn (): bool => auth()->user() instanceof User && auth()->user()->isAdmin();

        return $schema->components([
            Section::make('Data nasabah')->schema([
                TextInput::make('nama')->required()->maxLength(150),
                TextInput::make('email')->label('Email Google')->email()->required()->maxLength(191)
                    ->helperText('Nasabah login di aplikasi Android dengan akun Google ini.')
                    ->rule(fn (?Nasabah $record) => Rule::unique('users', 'email')->ignore($record?->user_id)),
                TextInput::make('no_hp')->label('No. HP')->required()->tel()->regex('/^(\+62|62|0)8[0-9]{7,12}$/')
                    ->unique(Nasabah::class, 'no_hp', ignoreRecord: true),
                TextInput::make('alamat_rt_rw')->label('Alamat RT/RW')->required()->maxLength(255),
                TextInput::make('nisn_atau_nik')->label('NIK')->numeric()->minLength(10)->maxLength(16)->helperText('Data sensitif: hanya tampil untuk petugas/admin.'),
                FileUpload::make('foto_ktp_kk_path')->label('Foto KTP/KK')->image()->maxSize(config('ecowin.upload.foto_max_kb'))
                    ->disk(config('ecowin.upload.disk'))->directory('identitas')->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
            ])->columns(2),
            Section::make('Bank Sampah & status')->schema([
                Select::make('bank_sampah_id')->label('Bank Sampah / RT')
                    ->options(fn () => BankSampah::query()->where('status', 'aktif')->orderBy('nama_bank_sampah')->pluck('nama_bank_sampah', 'id'))
                    ->required($isAdmin)->visible($isAdmin)->searchable(),
                Select::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])->default('aktif')->required()->visibleOn('edit'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('nomor_nasabah')->label('No.')->searchable()->copyable(),
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email Google')->searchable()->toggleable(),
                TextColumn::make('no_hp')->label('No. HP')->searchable(),
                TextColumn::make('saldo')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'gray'),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->filters([
                SelectFilter::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),
                SelectFilter::make('bank_sampah_id')->label('Bank Sampah')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cetak_kartu')->label('QR Card')->icon('heroicon-o-qr-code')
                    ->url(fn (Nasabah $record): string => route('admin.nasabah.kartu', $record))->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('scan_kartu')->label('Scan QR')->icon('heroicon-o-qr-code')->url(fn (): string => route('admin.scan-kartu')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNasabahs::route('/'),
            'create' => CreateNasabah::route('/create'),
            'edit' => EditNasabah::route('/{record}/edit'),
        ];
    }
}
