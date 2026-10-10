<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identitas warna per role: Admin = Indigo, Petugas = Emerald.
 */
class ApplyRoleTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        FilamentColor::register([
            'primary' => $user instanceof User && $user->isAdmin() ? Color::Indigo : Color::Emerald,
        ]);

        return $next($request);
    }
}
