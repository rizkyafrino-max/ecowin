<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureAccountIsActive::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [EnsureAccountIsActive::class]);
        // Cek akun nonaktif SEBELUM middleware autentikasi agar sesi lama langsung diakhiri.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, EnsureAccountIsActive::class);
        // Cek role sebelum route model binding: tidak membocorkan keberadaan ID ke role yang tidak berhak.
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureUserHasRole::class);
        $middleware->throttleApi('api');
        // Tamu yang membuka halaman web terproteksi (scan kartu, cetak laporan, dll.) diarahkan ke halaman masuk, bukan error.
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Jangan bocorkan nama model/tabel internal pada respons 404 API.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') && $e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Data tidak ditemukan.'], 404);
            }
        });

        // Sesi/CSRF kedaluwarsa pada halaman web: kembali ke Login dengan pesan jelas (bukan halaman "Page Expired").
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->is('api/*') && ! $request->expectsJson() && ! $request->hasHeader('X-Livewire')) {
                return redirect()->route('filament.admin.auth.login')->with('auth_error', 'Sesi Anda berakhir. Silakan masuk kembali.');
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage() ?: 'Akses ditolak.'], 403);
            }
        });
    })->create();
