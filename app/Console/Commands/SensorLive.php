<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\SensorLog;

class SensorLive extends Command
{
    protected $signature = 'sensor:live {--interval=1500 : Interval dalam milidetik (default 1500ms)}';
    protected $description = 'Ambil data Blynk secara real-time dengan interval pendek (default 1.5 detik)';

    public function handle()
    {
        $intervalMs = (int) $this->option('interval');
        $intervalUs = $intervalMs * 1000; // convert ke microseconds untuk usleep()

        $token = env('BLYNK_TOKEN');
        $server = env('BLYNK_SERVER', 'blynk.cloud');

        $this->info("🚀 SENSOR LIVE dimulai | Interval: {$intervalMs}ms | Server: {$server}");
        $this->info("   Tekan Ctrl+C untuk berhenti.");
        $this->line(str_repeat('-', 50));

        $count = 0;

        while (true) {
            $count++;
            try {
                // Tarik data V0 sampai V6 sekaligus dengan format yang didukung Blynk
                $pins = ['v0', 'v1', 'v2', 'v3', 'v4', 'v5', 'v6'];
                $queryString = "token=" . $token . "&" . implode("&", $pins);

                $response = Http::timeout(5)->get("https://{$server}/external/api/get?" . $queryString);

                if ($response->successful()) {
                    $raw = $response->json();

                    // Normalise keys to lowercase
                    $data = [];
                    foreach ($raw as $k => $v) {
                        $data[strtolower($k)] = $v;
                    }

                    // DEBUG: Log raw data to console
                    $this->warn("RAW BLYNK: " . json_encode($data));

                    // Only save if we have actual core data
                    if (isset($data['v0']) || isset($data['v2'])) {

                        // Parse Core Values
                        $temp = isset($data['v0']) ? (float) $data['v0'] : 0.0;
                        $hum = isset($data['v1']) ? (float) $data['v1'] : 0.0;

                        // Individual sensor values from new ESP32 code format
                        $smoke1 = isset($data['v2']) ? (int) $data['v2'] : 0;
                        $flame1 = isset($data['v3']) ? ((int) $data['v3'] == 1) : false;
                        $smoke2 = isset($data['v4']) ? (int) $data['v4'] : 0;
                        $smoke3 = isset($data['v5']) ? (int) $data['v5'] : 0;
                        $flame2 = isset($data['v6']) ? ((int) $data['v6'] == 1) : false;

                        // Calculate aggregates
                        $smoke = max($smoke1, $smoke2, $smoke3);
                        $fire = ($flame1 || $flame2);

                        SensorLog::create([
                            'temp' => $temp,
                            'hum' => $hum,
                            'smoke' => $smoke,
                            'fire' => $fire,
                            'smoke1' => $smoke1,
                            'smoke2' => $smoke2,
                            'smoke3' => $smoke3,
                            'flame1' => $flame1,
                            'flame2' => $flame2,
                        ]);

                        // Visual output for the console
                        $status = $fire ? "🔥:BAHAYA!" : "🔥:OK";
                        $this->info("[#" . now()->format('His') . "] T:{$temp}°C | S1:{$smoke1} S2:{$smoke2} S3:{$smoke3} | {$status}");
                    }
                } else {
                    $this->warn("[#$count] Gagal: HTTP " . $response->status());
                }

            } catch (\Exception $e) {
                $this->error("[#$count] Error: " . $e->getMessage());
            }

            usleep($intervalUs);
        }
    }
}
