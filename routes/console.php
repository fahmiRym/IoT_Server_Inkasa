<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    for ($i = 0; $i < 60; $i++) {
        Artisan::call('sensor:fetch');
        sleep(1);
    }
})->everyMinute();

