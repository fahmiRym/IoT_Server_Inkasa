<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SensorLog;
$logs = SensorLog::latest()->take(5)->get();
foreach ($logs as $log) {
    echo "ID: {$log->id} | T: {$log->temp} | H: {$log->hum} | S: {$log->smoke} | Time: {$log->created_at}\n";
}
