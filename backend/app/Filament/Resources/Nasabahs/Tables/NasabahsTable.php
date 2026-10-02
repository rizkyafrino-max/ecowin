<?php

namespace App\Filament\Resources\Nasabahs\Tables;

use App\Models\AuditLog;
use App\Models\Nasabah;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NasabahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->searchable(),
                TextColumn::make('username')->searchable(),
                TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah'),
                TextColumn::make('no_hp')
                    ->searchable(),
                TextColumn::make('alamat_rt_rw')
                    ->searchable(),
                TextColumn::make('nisn_atau_nik')
                    ->searchable(),
                TextColumn::make('status_verifikasi')
                    ->badge(),
                TextColumn::make('saldo')->money('IDR')->state(fn ($record): int => $record->saldo),
                TextColumn::make('dibuat_oleh')
                    ->numeric()
                    ->sortable(),
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
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reset_pin')
                    ->label('Reset PIN')
                    ->requiresConfirmation()
                    ->modalDescription('PIN nasabah akan diatur ke PIN default. Sampaikan PIN ini kepada nasabah secara langsung.')
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isPetugas())
                    ->action(function (Nasabah $record): void {
                        $user = auth()->user();
                        abort_unless($user?->isAdmin() || ($user?->isPetugas() && $user->bank_sampah_id === $record->bank_sampah_id), 403);

                        $pin = (string) config('ecowin.default_nasabah_pin', '123456');
                        $record->update(['pin' => $pin]);
                        AuditLog::create([
                            'user_id' => $user->id,
                            'bank_sampah_id' => $record->bank_sampah_id,
                            'aksi' => 'reset_pin_nasabah',
                            'detail' => json_encode(['nasabah_id' => $record->id]),
                        ]);

                        Notification::make()->title('PIN direset ke '.$pin)->warning()->persistent()->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
