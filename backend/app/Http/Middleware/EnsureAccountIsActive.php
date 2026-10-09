<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan/dicabut tidak boleh lagi memakai sesi atau token yang masih tersimpan.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $user->tokens()->delete();

                return response()->json(['message' => 'Akun Anda dinonaktifkan.'], Response::HTTP_FORBIDDEN);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')->with('auth_error', 'Akun Anda dinonaktifkan.');
        }

        return $next($request);
    }
}
