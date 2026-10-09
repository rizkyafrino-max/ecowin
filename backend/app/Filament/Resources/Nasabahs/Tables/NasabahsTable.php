<?php

namespace App\Filament\Resources\Nasabahs\Tables;

use App\Services\NasabahService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NasabahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('username')->label('Username')->searchable()->copyable(),
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable(),
                TextColumn::make('alamat_rt_rw')
                    ->label('Alamat RT/RW')
                    ->searchable(),
                TextColumn::make('status_verifikasi')
                    ->label('Verifikasi')->badge()->color(fn (string $state): string => $state === 'verified' ? 'success' : 'warning'),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah')->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_verifikasi')->label('Verifikasi')->options(['pending' => 'Pending', 'verified' => 'Verified']),
            ])
            ->recordActions([
                Action::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status_verifikasi === 'pending' && auth()->user()?->can('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi nasabah ini?')
                    ->modalDescription('Pastikan nama, nomor HP, dan alamat sudah sesuai. Setelah diverifikasi, nasabah dapat mengajukan penarikan saldo.')
                    ->action(function ($record): void {
                        app(NasabahService::class)->verifikasi(auth()->user(), $record);
                        Notification::make()->title('Nasabah diverifikasi')->success()->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status_verifikasi === 'pending' && auth()->user()?->can('update', $record))
                    ->modalHeading('Tolak pendaftaran ini?')
                    ->modalDescription('Akun akan dinonaktifkan dan nasabah tidak bisa masuk. Alasan tercatat di audit log.')
                    ->schema([Textarea::make('alasan')->label('Alasan penolakan')->required()->minLength(5)->maxLength(500)])
                    ->action(function ($record, array $data): void {
                        app(NasabahService::class)->tolak(auth()->user(), $record, $data['alasan']);
                        Notification::make()->title('Pendaftaran ditolak')->success()->send();
                    }),
                EditAction::make(),
                Action::make('cetak_kartu')
                    ->label('Cetak kartu')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record): string => route('admin.nasabah.kartu', $record))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('scan_kartu')
                    ->label('Scan kartu')
                    ->icon('heroicon-o-qr-code')
                    ->url(route('admin.scan-kartu')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
