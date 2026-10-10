<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Halaman login hanya menampilkan tombol "Continue with Google".
 * Login email/password dinonaktifkan sepenuhnya (authenticate() selalu ditolak).
 */
class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Masuk ke EcoWin';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.auth.google-login'),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        abort(403, 'Login password tidak tersedia. Gunakan Google.');
    }
}
