<?php

use App\Http\Controllers\Auth\GoogleWebAuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\KartuNasabahController;
use App\Http\Controllers\LaporanCetakController;
use App\Http\Controllers\LaporanEksporController;
use App\Http\Controllers\ScanKartuController;
use App\Http\Controllers\SecureFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Foto profil unggahan (kode acak 32 karakter; hanya JPEG olahan server). Ringan dan bisa di-cache lama.
Route::get('/foto/{kode}', \App\Http\Controllers\FotoProfilTampilController::class)->where('kode', '[A-Za-z0-9]{32}')->middleware('throttle:300,1')->name('foto-profil');

// Satu halaman login untuk semua role. /masuk meneruskan parameter PKCE dari aplikasi web Nasabah.
Route::get('/masuk', fn (Illuminate\Http\Request $r) => redirect()->route('filament.admin.auth.login', array_filter($r->only('app', 'c'))));

// Pendaftaran Nasabah mandiri (identitas Google diverifikasi server, role selalu nasabah).
// Halaman (GET) memakai batas yang lebih longgar agar banyak orang dari satu jaringan (Wi-Fi RT/RW, IP bersama operator)
// tidak saling memblokir; pengiriman data (POST) tetap memakai batas ketat "auth".
Route::middleware('throttle:auth-page')->group(function (): void {
    Route::get('/daftar', [RegistrationController::class, 'show'])->name('daftar');
    Route::get('/daftar/lengkapi', [RegistrationController::class, 'lengkapi'])->name('daftar.lengkapi');
});
Route::post('/daftar/lengkapi', [RegistrationController::class, 'simpan'])->middleware('throttle:auth')->name('daftar.simpan');

Route::middleware(['guest', 'throttle:auth-page'])->group(function (): void {
    Route::get('/auth/google/redirect', [GoogleWebAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleWebAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'role:admin,petugas'])->group(function (): void {
    Route::get('/admin/nasabah/{nasabah}/kartu', [KartuNasabahController::class, 'show'])->name('admin.nasabah.kartu');
    Route::get('/admin/laporan/cetak', [LaporanCetakController::class, 'show'])->middleware('throttle:30,1')->name('admin.laporan.cetak');
    Route::get('/admin/laporan/ekspor/{jenis}', [LaporanEksporController::class, 'csv'])->middleware('throttle:20,1')->where('jenis', 'anorganik|organik|penarikan|rekap')->name('admin.laporan.ekspor');
    Route::get('/admin/scan-kartu', [ScanKartuController::class, 'page'])->name('admin.scan-kartu');
    Route::get('/admin/scan-kartu/{token}', [ScanKartuController::class, 'lookup'])->name('admin.scan-kartu.lookup');
    Route::get('/admin/berkas/biopori/{aktivitas}', [SecureFileController::class, 'biopori'])->name('admin.berkas.biopori');
    Route::get('/admin/berkas/laporan/{laporanKendala}', [SecureFileController::class, 'laporan'])->name('admin.berkas.laporan');
    Route::get('/admin/berkas/identitas/{nasabah}', [SecureFileController::class, 'identitas'])->name('admin.berkas.identitas');
});
