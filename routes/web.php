<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/api/live-data', [DashboardController::class, 'getLiveMetrics']);
Route::get('/export', [DashboardController::class, 'export'])->name('export.csv');
Route::get('/export/pdf', [DashboardController::class, 'exportPdf'])->name('export.pdf');