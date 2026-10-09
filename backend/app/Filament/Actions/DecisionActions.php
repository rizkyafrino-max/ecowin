<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Tombol Setujui/Tolak yang konsisten untuk semua alur persetujuan.
 * Otorisasi dicek ulang di service (bukan hanya visibilitas tombol).
 */
class DecisionActions
{
    /**
     * @param  Closure(Model): bool  $visible
     * @param  Closure(Model, ?string): mixed  $handler
     */
    public static function approve(Closure $visible, Closure $handler, string $label = 'Setujui', bool $catatanRequired = false): Action
    {
        return static::make('approve', $label, 'success', 'heroicon-o-check-circle', $visible, $handler, $catatanRequired);
    }

    /**
     * @param  Closure(Model): bool  $visible
     * @param  Closure(Model, ?string): mixed  $handler
     */
    public static function reject(Closure $visible, Closure $handler, string $label = 'Tolak'): Action
    {
        return static::make('reject', $label, 'danger', 'heroicon-o-x-circle', $visible, $handler, true);
    }

    private static function make(string $name, string $label, string $color, string $icon, Closure $visible, Closure $handler, bool $catatanRequired): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($icon)
            ->visible(fn (Model $record): bool => $visible($record))
            ->requiresConfirmation()
            ->schema([
                Textarea::make('catatan')->label('Catatan')->required($catatanRequired)->maxLength(1000),
            ])
            ->action(function (Model $record, array $data) use ($handler, $label): void {
                try {
                    $handler($record, $data['catatan'] ?? null);
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();

                    return;
                } catch (AuthorizationException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title($label.' berhasil.')->send();
            });
    }
}
