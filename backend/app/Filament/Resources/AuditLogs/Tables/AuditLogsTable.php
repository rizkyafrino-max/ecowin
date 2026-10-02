<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            TextColumn::make('user.nama')->label('Pengguna')->searchable(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah')->placeholder('Global'),
            TextColumn::make('aksi')->badge()->searchable(),
            TextColumn::make('detail')->limit(100)->wrap(),
        ])->filters([
            SelectFilter::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah'),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }
}
