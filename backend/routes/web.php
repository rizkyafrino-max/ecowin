<?php

use App\Http\Controllers\Auth\GoogleWebAuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\KartuNasabahController;
use App\Http\Controllers\ScanKartuController;
use App\Http\Controllers\SecureFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Satu halaman login untuk semua role. /masuk meneruskan parameter PKCE dari aplikasi web Nasabah.
Route::get('/masuk', fn (Illuminate\Http\Request $r) => redirect()->route('filament.admin.auth.login', array_filter($r->only('app', 'c'))));

// Pendaftaran Nasabah mandiri (identitas Google diverifikasi server, role selalu nasabah).
Route::middleware('throttle:auth')->group(function (): void {
    Route::get('/daftar', [RegistrationController::class, 'show'])->name('daftar');
    Route::get('/daftar/lengkapi', [RegistrationController::class, 'lengkapi'])->name('daftar.lengkapi');
    Route::post('/daftar/lengkapi', [RegistrationController::class, 'simpan'])->name('daftar.simpan');
});

Route::middleware(['guest', 'throttle:auth'])->group(function (): void {
    Route::get('/auth/google/redirect', [GoogleWebAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleWebAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'role:admin,petugas'])->group(function (): void {
    Route::get('/admin/nasabah/{nasabah}/kartu', [KartuNasabahController::class, 'show'])->name('admin.nasabah.kartu');
    Route::get('/admin/scan-kartu', [ScanKartuController::class, 'page'])->name('admin.scan-kartu');
    Route::get('/admin/scan-kartu/{token}', [ScanKartuController::class, 'lookup'])->name('admin.scan-kartu.lookup');
    Route::get('/admin/berkas/biopori/{aktivitas}', [SecureFileController::class, 'biopori'])->name('admin.berkas.biopori');
    Route::get('/admin/berkas/identitas/{nasabah}', [SecureFileController::class, 'identitas'])->name('admin.berkas.identitas');
});
