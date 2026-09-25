<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HargaSampahController;
use App\Http\Controllers\KoreksiTransaksiController;
use App\Http\Controllers\LaporanKendalaController;
use App\Http\Controllers\NasabahController;
use App\Http\Controllers\PenarikanSaldoController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/nasabah', [NasabahController::class, 'index']);
    Route::post('/nasabah', [NasabahController::class, 'store']);
    Route::get('/nasabah/scan/{token}', [NasabahController::class, 'scanByToken']);
    Route::get('/nasabah/{nasabah}', [NasabahController::class, 'show']);
    Route::patch('/nasabah/{nasabah}/verifikasi', [NasabahController::class, 'verifikasi']);

    Route::get('/harga', [HargaSampahController::class, 'index']);
    Route::get('/harga/riwayat/{jenis_sampah_id}', [HargaSampahController::class, 'riwayat']);
    Route::post('/harga', [HargaSampahController::class, 'store']);

    Route::post('/transaksi/anorganik', [TransaksiController::class, 'storeAnorganik']);
    Route::post('/transaksi/organik', [TransaksiController::class, 'storeOrganik']);
    Route::get('/transaksi', [TransaksiController::class, 'index']);

    Route::post('/penarikan', [PenarikanSaldoController::class, 'store']);
    Route::patch('/penarikan/{penarikan}/proses', [PenarikanSaldoController::class, 'proses']);

    Route::post('/laporan-kendala', [LaporanKendalaController::class, 'store']);
    Route::get('/laporan-kendala', [LaporanKendalaController::class, 'index']);
    Route::patch('/laporan-kendala/{laporanKendala}', [LaporanKendalaController::class, 'update']);

    Route::post('/koreksi', [KoreksiTransaksiController::class, 'store']);
    Route::patch('/koreksi/{koreksi}/setujui', [KoreksiTransaksiController::class, 'setujui']);

    Route::get('/audit-log', [AuditLogController::class, 'index']);
});