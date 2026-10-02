<?php

namespace App\Filament\Resources\LaporanKendalas\Tables;

use App\Models\AuditLog;
use App\Models\LaporanKendala;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LaporanKendalasTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            TextColumn::make('kategori')->badge(),
            TextColumn::make('bankSampah.nama_bank_sampah')->label('Bank sampah'),
            TextColumn::make('pelaporNasabah.nama')->label('Nasabah')->placeholder(fn (LaporanKendala $record): string => $record->pelaporUser?->nama ?? '—'),
            TextColumn::make('deskripsi')->limit(90)->wrap(),
            TextColumn::make('status')->badge(),
            TextColumn::make('catatan_admin')->limit(50),
        ])->filters([
            SelectFilter::make('status')->options(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak']),
            SelectFilter::make('bank_sampah_id')->relationship('bankSampah', 'nama_bank_sampah')->visible(fn () => auth()->user()->isAdmin()),
        ])->recordActions([
            Action::make('tinjau')
                ->label('Tinjau')
                ->visible(fn (LaporanKendala $record): bool => $record->status === 'menunggu' && (auth()->user()->isAdmin() || auth()->user()->bank_sampah_id === $record->bank_sampah_id))
                ->schema([
                    Select::make('status')->options(['disetujui' => 'Ditangani', 'ditolak' => 'Ditolak'])->required(),
                    Textarea::make('catatan_admin')->label('Catatan')->maxLength(2000),
                ])
                ->action(function (LaporanKendala $record, array $data): void {
                    $record->update([
                        ...$data,
                        'ditinjau_oleh' => auth()->id(),
                    ]);
                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'bank_sampah_id' => $record->bank_sampah_id,
                        'aksi' => 'tinjau_laporan_kendala',
                        'detail' => json_encode(['laporan_id' => $record->id, 'status' => $data['status']]),
                    ]);
                }),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }
}
