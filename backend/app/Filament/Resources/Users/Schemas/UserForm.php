<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\BankSampah;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->password()->revealable()->required(fn (?User $record): bool => $record === null)->dehydrated(fn (?string $state): bool => filled($state)),
            Select::make('bank_sampah_id')
                ->label('Bank sampah yang ditugaskan')
                ->options(fn (?User $record): array => BankSampah::query()
                    ->where(function (Builder $query) use ($record): void {
                        $query->whereDoesntHave('petugas', fn (Builder $query) => $query->where('role', 'petugas'));
                        if ($record?->bank_sampah_id) {
                            $query->orWhereKey($record->bank_sampah_id);
                        }
                    })
                    ->orderBy('nama_bank_sampah')
                    ->pluck('nama_bank_sampah', 'id')
                    ->all())
                ->searchable()
                ->required()
                ->rules([
                    fn (?User $record) => function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                        $bank = BankSampah::find($value);
                        $assignedUserId = $bank?->petugas?->id;
                        if (! $bank || ($assignedUserId && $assignedUserId !== $record?->id)) {
                            $fail('Bank sampah tersebut sudah ditugaskan kepada petugas lain.');
                        }
                    },
                ]),
        ]);
    }
}
