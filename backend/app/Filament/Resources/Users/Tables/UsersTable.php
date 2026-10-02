<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama')->searchable()->sortable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah')->placeholder('Belum ditugaskan'),
        ])->recordActions([
            EditAction::make(),
            Action::make('reset_password')
                ->label('Reset password')
                ->requiresConfirmation()
                ->modalDescription('Sistem membuat password sementara baru. Sampaikan password tersebut secara langsung kepada petugas.')
                ->visible(fn (): bool => auth()->user()?->isAdmin() === true)
                ->action(function (User $record): void {
                    abort_unless(auth()->user()?->isAdmin(), 403);
                    $password = Str::password(14);
                    $record->update(['password' => $password]);
                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'bank_sampah_id' => $record->bank_sampah_id,
                        'aksi' => 'reset_password_petugas',
                        'detail' => json_encode(['user_id' => $record->id]),
                    ]);

                    Notification::make()->title('Password sementara: '.$password)->warning()->persistent()->send();
                }),
        ])->toolbarActions([]);
    }
}
