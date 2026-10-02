<?php

namespace App\Filament\Resources\Penarikans\Tables;

use App\Models\PenarikanSaldo;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PenarikanSaldoTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Diajukan')->dateTime()->sortable(),
            TextColumn::make('nasabah.nama')->searchable()->sortable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah'),
            TextColumn::make('jumlah')->money('IDR')->sortable(),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'pending' => 'warning', 'selesai' => 'success', 'ditolak' => 'danger', default => 'gray'
            }),
            TextColumn::make('pemroses.nama')->label('Diproses oleh'),
        ])->filters([
            SelectFilter::make('status')->options(['pending' => 'Menunggu', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak']),
            SelectFilter::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn () => auth()->user()->isAdmin()),
        ])->recordActions([
            Action::make('selesaikan')
                ->label('ACC')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (PenarikanSaldo $record): bool => $record->status === 'pending')
                ->action(fn (PenarikanSaldo $record) => self::process($record, 'selesai')),
            Action::make('tolak')
                ->label('Tolak')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (PenarikanSaldo $record): bool => $record->status === 'pending')
                ->action(fn (PenarikanSaldo $record) => self::process($record, 'ditolak')),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }

    private static function process(PenarikanSaldo $record, string $status): void
    {
        $record->update(['status' => $status, 'diproses_oleh' => auth()->id()]);
    }
}
