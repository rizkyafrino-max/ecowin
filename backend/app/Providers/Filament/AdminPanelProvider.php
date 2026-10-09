<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditUserProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\ApplyRoleTheme;
use App\Http\Middleware\EnsureAccountIsActive;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

/**
 * Satu panel untuk Admin & Petugas. Data dibatasi lewat Policy + query scope,
 * menu yang tidak berhak otomatis tersembunyi, dan warna identitas mengikuti role
 * (Admin = Indigo, Petugas = Emerald).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile(EditUserProfile::class)
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName('EcoWin')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('images/ecowin-emblem.png'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Operasional'),
                NavigationGroup::make('Anorganik'),
                NavigationGroup::make('Organik'),
                NavigationGroup::make('Keuangan'),
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Sistem'),
            ])
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.global-ui-styles'))
            ->renderHook(PanelsRenderHook::BODY_END, fn (): View => view('filament.global-footer'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureAccountIsActive::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ApplyRoleTheme::class,
            ]);
    }
}
