<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
            TextColumn::make('email')->label('Email')->searchable(),
            TextColumn::make('role')->label('Peran')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->color(fn (string $state): string => $state === 'admin' ? 'primary' : 'success'),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah')->placeholder('Semua bank sampah'),
            TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
        ])->filters([
            SelectFilter::make('role')->label('Peran')->options(['admin' => 'Admin', 'petugas' => 'Petugas']),
        ])->recordActions([EditAction::make()])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
