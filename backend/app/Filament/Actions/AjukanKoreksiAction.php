<?php

namespace App\Filament\Actions;

use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Services\KoreksiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Transaksi tidak diedit bebas: petugas mengajukan koreksi beserta alasan.
 */
class AjukanKoreksiAction
{
    public static function make(string $tipe): Action
    {
        return Action::make('ajukan_koreksi')
            ->label('Ajukan koreksi')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->isStaff() ?? false)
            ->schema([
                TextInput::make('berat_kg')->label('Berat yang benar')->suffix('kg')->numeric()->minValue(0.01)->maxValue(10000)->required()
                    ->default(fn (TransaksiAnorganik|TransaksiOrganik $record) => $record->berat_kg),
                Textarea::make('alasan')->label('Alasan koreksi')->required()->minLength(10)->maxLength(1000),
            ])
            ->action(function (TransaksiAnorganik|TransaksiOrganik $record, array $data) use ($tipe): void {
                try {
                    app(KoreksiService::class)->ajukan(auth()->user(), $tipe, $record->id, (float) $data['berat_kg'], $data['alasan']);
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();

                    return;
                }

                Notification::make()->success()->title('Koreksi diajukan dan menunggu persetujuan Admin.')->send();
            });
    }
}
