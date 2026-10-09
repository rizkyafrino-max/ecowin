<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BioporiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HargaSampahController;
use App\Http\Controllers\Api\KoreksiTransaksiController;
use App\Http\Controllers\Api\LaporanKendalaController;
use App\Http\Controllers\Api\NasabahController;
use App\Http\Controllers\Api\PenarikanSaldoController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransaksiAnorganikController;
use App\Http\Controllers\Api\TransaksiOrganikController;
use App\Http\Controllers\Api\WebLoginController;
use Illuminate\Support\Facades\Route;

/*
| Autentikasi: HANYA Google Sign-In. Tidak ada login password/PIN.
*/
Route::post('/auth/google', [AuthController::class, 'google'])->middleware('throttle:auth')->name('api.auth.google');

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('api.auth.register');
Route::get('/bank-sampah/publik', [AuthController::class, 'bankSampahPublik'])->middleware('throttle:auth')->name('api.bank-sampah.publik');

// Serah-terima login web Nasabah: kode sekali pakai + PKCE verifier -> token.
Route::post('/auth/exchange', [WebLoginController::class, 'exchange'])->middleware('throttle:auth')->name('api.auth.exchange');

// Refresh token hanya bisa dipakai untuk endpoint refresh (ability "refresh").
Route::post('/auth/refresh', [AuthController::class, 'refresh'])
    ->middleware(['auth:sanctum', 'active', 'abilities:refresh', 'throttle:auth'])
    ->name('api.auth.refresh');

// Semua endpoint lain wajib access token (ability "access") + akun aktif.
Route::middleware(['auth:sanctum', 'active', 'abilities:access'])->name('api.')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.put');

    Route::get('/dashboard', [DashboardController::class, 'summary'])->name('dashboard.alias');
    Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');

    // Harga
    Route::get('/harga', [HargaSampahController::class, 'index'])->name('harga.index');
    Route::get('/harga/jenis/{jenisSampah}', [HargaSampahController::class, 'show'])->name('harga.show');
    Route::get('/harga/jenis/{jenisSampah}/riwayat', [HargaSampahController::class, 'riwayat'])->middleware('role:admin')->name('harga.riwayat');
    Route::post('/harga', [HargaSampahController::class, 'store'])->middleware('role:admin')->name('harga.store');

    // Nasabah (data milik nasabah yang login)
    Route::middleware('role:nasabah')->prefix('me')->name('me.')->group(function (): void {
        Route::get('/saldo', [NasabahController::class, 'saldo'])->name('saldo');
        Route::get('/mutasi-saldo', [NasabahController::class, 'mutasi'])->name('mutasi');
        Route::get('/qr', [NasabahController::class, 'qr'])->name('qr');
    });

    // Alias singkat untuk aplikasi Web & Android Nasabah (kontrak sama dengan endpoint /me/* dan /organik/biopori)
    Route::middleware('role:nasabah')->group(function (): void {
        Route::get('/saldo', [NasabahController::class, 'saldo'])->name('alias.saldo');
        Route::get('/saldo/history', [NasabahController::class, 'mutasi'])->name('alias.saldo.history');
        Route::get('/qr', [NasabahController::class, 'qr'])->name('alias.qr');
        Route::get('/transaksi', [TransaksiAnorganikController::class, 'index'])->name('alias.transaksi');
        Route::get('/biopori', [BioporiController::class, 'index'])->name('alias.biopori.index');
        Route::post('/biopori', [BioporiController::class, 'store'])->middleware('throttle:uploads')->name('alias.biopori.store');
    });

    // Nasabah (manajemen oleh admin/petugas)
    Route::middleware('role:admin,petugas')->group(function (): void {
        Route::get('/nasabah', [NasabahController::class, 'index'])->name('nasabah.index');
        Route::post('/nasabah', [NasabahController::class, 'store'])->name('nasabah.store');
        Route::get('/nasabah/scan/{token}', [NasabahController::class, 'scan'])->name('nasabah.scan');
    });
    Route::get('/nasabah/{nasabah}', [NasabahController::class, 'show'])->name('nasabah.show');

    // Anorganik
    Route::get('/transaksi/anorganik', [TransaksiAnorganikController::class, 'index'])->name('anorganik.index');
    Route::get('/transaksi/anorganik/{transaksi}', [TransaksiAnorganikController::class, 'show'])->name('anorganik.show');
    Route::post('/transaksi/anorganik', [TransaksiAnorganikController::class, 'store'])->middleware('role:admin,petugas')->name('anorganik.store');

    // Organik
    Route::get('/organik', [TransaksiOrganikController::class, 'index'])->name('organik.index');
    Route::get('/organik/{transaksi}', [TransaksiOrganikController::class, 'show'])->whereNumber('transaksi')->name('organik.show');
    Route::post('/organik', [TransaksiOrganikController::class, 'store'])->middleware('role:admin,petugas')->name('organik.store');

    // Organik (satu fitur): setoran + aktivitas dengan metode BioporiPrint
    Route::get('/organik/biopori/lokasi', [BioporiController::class, 'titik'])->name('biopori.titik');
    Route::get('/organik/biopori', [BioporiController::class, 'index'])->name('biopori.index');
    Route::post('/organik/biopori', [BioporiController::class, 'store'])->middleware(['role:nasabah', 'throttle:uploads'])->name('biopori.store');
    Route::get('/organik/biopori/{aktivitas}', [BioporiController::class, 'show'])->name('biopori.show');
    Route::get('/organik/biopori/{aktivitas}/foto', [BioporiController::class, 'foto'])->name('biopori.foto');
    Route::post('/organik/biopori/{aktivitas}/approve', [BioporiController::class, 'approve'])->middleware('role:admin,petugas')->name('biopori.approve');
    Route::post('/organik/biopori/{aktivitas}/reject', [BioporiController::class, 'reject'])->middleware('role:admin,petugas')->name('biopori.reject');

    // Penarikan
    Route::get('/penarikan', [PenarikanSaldoController::class, 'index'])->name('penarikan.index');
    Route::post('/penarikan', [PenarikanSaldoController::class, 'store'])->name('penarikan.store');
    Route::get('/penarikan/{penarikan}', [PenarikanSaldoController::class, 'show'])->name('penarikan.show');
    Route::middleware('role:admin,petugas')->group(function (): void {
        Route::post('/penarikan/{penarikan}/approve', [PenarikanSaldoController::class, 'approve'])->name('penarikan.approve');
        Route::post('/penarikan/{penarikan}/reject', [PenarikanSaldoController::class, 'reject'])->name('penarikan.reject');
        Route::post('/penarikan/{penarikan}/complete', [PenarikanSaldoController::class, 'complete'])->name('penarikan.complete');
    });

    // Koreksi transaksi: Admin atau Petugas (di Bank Sampahnya) mengajukan dan memutuskan, tercatat di audit log
    Route::middleware('role:admin,petugas')->group(function (): void {
        Route::get('/koreksi', [KoreksiTransaksiController::class, 'index'])->name('koreksi.index');
        Route::post('/koreksi', [KoreksiTransaksiController::class, 'store'])->name('koreksi.store');
        Route::post('/koreksi/{koreksi}/approve', [KoreksiTransaksiController::class, 'approve'])->name('koreksi.approve');
        Route::post('/koreksi/{koreksi}/reject', [KoreksiTransaksiController::class, 'reject'])->name('koreksi.reject');
    });
    Route::middleware('role:admin')->group(function (): void {
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    });

    // Laporan kendala
    Route::get('/laporan-kendala', [LaporanKendalaController::class, 'index'])->name('laporan-kendala.index');
    Route::post('/laporan-kendala', [LaporanKendalaController::class, 'store'])->name('laporan-kendala.store');
    Route::patch('/laporan-kendala/{laporanKendala}', [LaporanKendalaController::class, 'update'])->middleware('role:admin')->name('laporan-kendala.update');
});
