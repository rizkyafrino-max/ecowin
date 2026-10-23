<?php

namespace App\Providers;

use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\KategoriSampah;
use App\Models\Nasabah;
use App\Models\TitikBiopori;
use App\Models\User;
use App\Observers\AuditableObserver;
use App\Observers\NasabahObserver;
use App\Observers\UserObserver;
use App\Services\AuditLogger;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Auth\GoogleTokenVerifier;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoogleTokenVerifier::class, GoogleIdTokenVerifier::class);
    }

    public function boot(): void
    {
        // Formulir panel: bagian (Section) tersusun ke bawah selebar penuh, dan isian di dalamnya dua kolom di layar sedang ke atas.
        // Dua kartu bersisian membuat kolom isian hanya ±160px sehingga teks terpotong. Pengaturan eksplisit per formulir tetap menang.
        \Filament\Schemas\Schema::configureUsing(fn (\Filament\Schemas\Schema $schema) => $schema->columns(1));
        \Filament\Schemas\Components\Section::configureUsing(fn (\Filament\Schemas\Components\Section $section) => $section->columns(['default' => 1, 'md' => 2]));

        // Placeholder singkat agar tidak terpotong pada kolom sempit di HP.
        \Filament\Forms\Components\Select::configureUsing(fn (\Filament\Forms\Components\Select $select) => $select->placeholder('Pilih…'));

        // Saat test: gagal bila ada atribut yang diisi tapi tidak fillable.
        Model::preventSilentlyDiscardingAttributes($this->app->runningUnitTests());

        Nasabah::observe(NasabahObserver::class);
        User::observe(UserObserver::class);

        foreach ([User::class, BankSampah::class, Nasabah::class, KategoriSampah::class, JenisSampah::class, HargaSampah::class, TitikBiopori::class] as $model) {
            $model::observe(AuditableObserver::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('login', $event->user, actor: $event->user, detail: 'web');
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('logout', $event->user, actor: $event->user, detail: 'web');
            }
        });

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
            Limit::perHour(60)->by('auth-ip-hour:'.$request->ip()),
        ]);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}
