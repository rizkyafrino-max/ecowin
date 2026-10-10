<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Pengguna Admin/Petugas. Login hanya dengan Google: admin cukup mendaftarkan
 * email Google, tanpa password. Akun nasabah dikelola lewat menu Nasabah.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Pengguna & Petugas';

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', [Role::Admin->value, Role::Petugas->value]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas pengguna')->schema([
                TextInput::make('nama')->label('Nama lengkap')->required()->maxLength(150),
                TextInput::make('email')->label('Email Google')->email()->required()->maxLength(191)
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->helperText('Pengguna masuk dengan akun Google yang memakai email ini.'),
            ])->columns(2),
            Section::make('Akses sistem')->schema([
                Select::make('role')->label('Peran')->options([Role::Petugas->value => 'Petugas', Role::Admin->value => 'Admin'])->default(Role::Petugas->value)->required()->live(),
                Select::make('bank_sampah_id')->label('Bank Sampah')->relationship('bankSampah', 'nama_bank_sampah')->searchable()->preload()
                    ->required(fn (Get $get): bool => $get('role') === Role::Petugas->value)
                    ->visible(fn (Get $get): bool => $get('role') === Role::Petugas->value),
                Select::make('status')->label('Status akun')->options([User::STATUS_AKTIF => 'Aktif', User::STATUS_NONAKTIF => 'Nonaktif'])->default(User::STATUS_AKTIF)->required()
                    ->helperText('Menonaktifkan akun langsung mencabut seluruh sesi & token.'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
            TextColumn::make('email')->label('Email Google')->searchable(),
            TextColumn::make('role')->label('Peran')->badge()->formatStateUsing(fn (string $state): string => strtoupper($state))
                ->color(fn (string $state): string => $state === 'admin' ? 'info' : 'success'),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank Sampah')->placeholder('Semua'),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))
                ->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'danger'),
            TextColumn::make('last_login_at')->label('Login terakhir')->since()->placeholder('Belum pernah'),
        ])->filters([
            SelectFilter::make('role')->label('Peran')->options(['admin' => 'Admin', 'petugas' => 'Petugas']),
            SelectFilter::make('status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
