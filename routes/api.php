<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Pintu gerbang ingest ESP32.
// Rate-limit: 120 req/menit per IP + 180 req/menit per API key
// (ESP32 push ~30/menit, jadi buffer 4x untuk retry burst).
Route::post('/sensor/store', [SensorController::class, 'store'])
    ->middleware('throttle:sensor-ingest');
