<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$chartConfig = [
    'type' => 'line',
    'data' => [
        'labels' => ['A', 'B'],
        'datasets' => [
            [
                'label' => 'Avg Temp (°C)',
                'data' => [25.5, 26.0]
            ]
        ]
    ]
];

try {
    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
        ->timeout(15)
        ->post('https://quickchart.io/chart', [
            'chart' => $chartConfig,
            'width' => 700,
            'height' => 300,
            'backgroundColor' => 'white',
            'format' => 'png'
        ]);

    echo "STATUS: " . $response->status() . "\n";
    if (!$response->successful()) {
        echo "BODY: " . $response->body() . "\n";
    } else {
        echo "SUCCESS length: " . strlen($response->body()) . "\n";
    }
} catch (\Exception $e) {
    echo "EXC: " . $e->getMessage() . "\n";
}
