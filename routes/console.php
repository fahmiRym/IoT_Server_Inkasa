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
*/

// ---- Scheduled maintenance ----
// Backup DB harian jam 02:15 (idle jam server), simpan 14 versi terakhir.
Schedule::command('db:backup --keep=14')
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer();

// Prune motion clips harian jam 03:00, umur > 7 hari.
Schedule::command('motion:prune --days=7')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// Himbauan cek server lewat Telegram tiap 15:00 WIB (configurable via NOC_CHECK_HOUR).
// Trigger kalau tidak ada motion CCTV sejak jam 00:00. Juga set flag dashboard.
Schedule::command('noc:server-check-reminder')
    ->dailyAt(env('NOC_CHECK_HOUR', '15') . ':00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onOneServer();
