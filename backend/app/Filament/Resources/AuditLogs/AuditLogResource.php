<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Audit log: hanya dapat dilihat Admin, tidak dapat dibuat/diubah/dihapus.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'audit log';

    protected static ?string $pluralModelLabel = 'audit log';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aktivitas')->schema([
                TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s'),
                TextEntry::make('user.nama')->label('Pengguna')->placeholder('Sistem / tamu'),
                TextEntry::make('user.role')->label('Peran')->badge()->placeholder('-'),
                TextEntry::make('aksi')->badge()->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextEntry::make('model_type')->label('Model')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextEntry::make('model_id')->label('ID data')->placeholder('-'),
                TextEntry::make('ip_address')->label('IP')->placeholder('-'),
                TextEntry::make('user_agent')->label('User agent')->placeholder('-')->columnSpanFull(),
                TextEntry::make('detail')->placeholder('-')->columnSpanFull(),
            ])->columns(3),
            Section::make('Perubahan data')->schema([
                KeyValueEntry::make('data_sebelum')->label('Sebelum')->placeholder('-'),
                KeyValueEntry::make('data_sesudah')->label('Sesudah')->placeholder('-'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('user.nama')->label('Pengguna')->placeholder('Sistem')->searchable(),
                TextColumn::make('aksi')->label('Aksi')->badge()->searchable()->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('model_type')->label('Model')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextColumn::make('model_id')->label('ID'),
                TextColumn::make('ip_address')->label('IP')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('aksi')->options(fn (): array => AuditLog::query()->distinct()->orderBy('aksi')->pluck('aksi')->mapWithKeys(fn (string $a): array => [$a => ucfirst(str_replace('_', ' ', $a))])->all()),
                SelectFilter::make('user_id')->label('Pengguna')->relationship('user', 'nama')->searchable(),
                Filter::make('tanggal')->schema([DatePicker::make('dari'), DatePicker::make('sampai')])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                        ->when($data['sampai'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
