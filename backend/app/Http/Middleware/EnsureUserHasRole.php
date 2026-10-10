<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian: ->middleware('role:admin,petugas'). Role selalu dibaca dari database.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->role, $roles, true), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke fitur ini.');

        return $next($request);
    }
}
