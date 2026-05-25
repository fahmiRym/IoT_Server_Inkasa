<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| DINONAKTIFKAN — penyebab kuota message Blynk habis.
|--------------------------------------------------------------------------
| Polling ini menarik data dari Blynk Cloud tiap 1 detik (60x/menit),
| dan tiap panggilan membaca 7 pin (v0-v6) = 7 message Blynk.
| Total ~420 message/menit (~604.800/hari) -> kuota gratis (~30rb/bulan) habis.
|
| GANTINYA: ESP32 push langsung ke server via POST /api/sensor/store
| (SensorController@store). Jalur ini TIDAK memakai kuota Blynk sama sekali.
| Lihat docs/LOG-BLYNK-QUOTA.md untuk detail + firmware ESP32.
|
| Schedule::call(function () {
|     for ($i = 0; $i < 60; $i++) {
|         Artisan::call('sensor:fetch');
|         sleep(1);
|     }
| })->everyMinute();
*/

