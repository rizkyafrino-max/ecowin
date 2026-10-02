<?php

namespace App\Filament\Resources\Koreksis\Tables;

use App\Models\AuditLog;
use App\Models\KoreksiTransaksi;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class KoreksiTransaksiTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Diajukan')->dateTime()->sortable(),
            TextColumn::make('tipe_transaksi')->badge(),
            TextColumn::make('ringkasan_transaksi')->label('Transaksi')->wrap(),
            TextColumn::make('alasan')->limit(60)->wrap(),
            TextColumn::make('pengaju.nama')->label('Pengaju'),
            TextColumn::make('status')->badge(),
            TextColumn::make('penyetuju.nama')->label('Penyetuju'),
        ])->recordActions([
            Action::make('setujui')
                ->label('Setujui')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (KoreksiTransaksi $record): bool => $record->status === 'menunggu' && $record->diajukan_oleh !== auth()->id())
                ->action(fn (KoreksiTransaksi $record) => self::resolve($record, 'disetujui')),
            Action::make('tolak')
                ->label('Tolak')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (KoreksiTransaksi $record): bool => $record->status === 'menunggu' && $record->diajukan_oleh !== auth()->id())
                ->action(fn (KoreksiTransaksi $record) => self::resolve($record, 'ditolak')),
        ])->defaultSort('created_at', 'desc')->toolbarActions([]);
    }

    private static function resolve(KoreksiTransaksi $record, string $status): void
    {
        DB::transaction(function () use ($record, $status): void {
            $record = KoreksiTransaksi::query()->lockForUpdate()->findOrFail($record->id);
            abort_if($record->status !== 'menunggu', 422, 'Koreksi sudah diproses.');
            abort_if($record->diajukan_oleh === auth()->id(), 403);

            $model = $record->tipe_transaksi === 'anorganik' ? TransaksiAnorganik::class : TransaksiOrganik::class;
            $transaksi = $model::query()->findOrFail($record->transaksi_id);
            $user = auth()->user();
            abort_unless($user->isAdmin() || $transaksi->bank_sampah_id === $user->bank_sampah_id, 403);

            $record->update(['status' => $status, 'disetujui_oleh' => $user->id]);
            AuditLog::create([
                'user_id' => $user->id,
                'bank_sampah_id' => $transaksi->bank_sampah_id,
                'aksi' => 'koreksi_transaksi_'.$status,
                'detail' => json_encode(['koreksi_id' => $record->id, 'transaksi_id' => $transaksi->id]),
            ]);
        });
    }
}
