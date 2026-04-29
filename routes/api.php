<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ini pintu gerbang agar ESP32 bisa masuk
Route::post('/sensor/store', [SensorController::class, 'store']);