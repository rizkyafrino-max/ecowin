<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

/**
 * Profil: hanya nama yang bisa diubah. Email = akun Google (dikelola Admin),
 * tidak ada password karena login hanya lewat Google.
 */
class EditUserProfile extends EditProfile
{
    protected static ?string $title = 'Profil saya';

    public static function getLabel(): string
    {
        return 'Profil saya';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            TextInput::make('email')->label('Email Google')->disabled()->dehydrated(false),
        ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('nama')->label('Nama lengkap')->required()->maxLength(150)->autofocus();
    }
}
