<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bank_sampah_id')
                    ->numeric(),
                TextInput::make('aksi')
                    ->required(),
                Textarea::make('detail')
                    ->columnSpanFull(),
            ]);
    }
}
