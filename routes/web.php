<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ServerCheckController;

// --- AUTH (PUBLIC) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- OTP (tanpa auth — user belum login penuh) ---
Route::get('/otp', [AuthController::class, 'showOtp'])->name('otp.show');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:login');
Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:otp');

// --- PROTECTED ROUTES (perlu login) ---
Route::middleware('auth')->group(function () {
    // --- SEMUA USER (superadmin + operator) ---
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/api/live-data', [DashboardController::class, 'getLiveMetrics']);
    // Report export (Excel/PDF) — operator boleh akses untuk laporan harian
    Route::get('/export', [DashboardController::class, 'export'])->name('export.csv');
    Route::get('/export/pdf', [DashboardController::class, 'exportPdf'])->name('export.pdf');

    // Profile edit — sendiri
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.edit');
    Route::patch('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Device / Session Management (semua user)
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::delete('/devices/others', [DeviceController::class, 'destroyOthers'])->name('devices.destroyOthers');
    Route::delete('/devices/{id}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    // Web Push Notification (subscribe/unsubscribe endpoint untuk PWA)
    Route::post('/push/subscribe',   [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::get('/push/vapid-key',    [PushController::class, 'vapidKey'])->name('push.vapid');

    // Server-check reminder (himbauan cek server CCTV motion)
    Route::get('/api/server-check/status', [ServerCheckController::class, 'status'])->name('server-check.status');
    Route::post('/api/server-check/ack',   [ServerCheckController::class, 'acknowledge'])->name('server-check.ack');

    // --- SUPERADMIN ONLY ---
    Route::middleware('superadmin')->group(function () {
        // Database backup & restore (bahaya kalau salah restore)
        Route::get('/db/export', [DatabaseController::class, 'export'])->name('db.export');
        Route::post('/db/import', [DatabaseController::class, 'import'])->name('db.import');

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::patch('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });
});
