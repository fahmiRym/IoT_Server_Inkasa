<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\SensorLog;

class FetchBlynk extends Command
{
    // Nama perintah yang nanti dipanggil di terminal
    protected $signature = 'sensor:fetch';
    protected $description = 'Ambil data Blynk dan simpan ke DB';

    public function handle()
    {
        // Ambil konfigurasi dari .env
        $token = env('BLYNK_TOKEN');
        $server = env('BLYNK_SERVER', 'blynk.cloud');

        try {
            // Ambil data dari Blynk via API
            $response = Http::timeout(5)->get("https://{$server}/external/api/get", [
                'token' => $token,
                'v0' => '',
                'v1' => '',
                'v2' => '',
                'v3' => '',
                'v4' => '',
                'v5' => '',
                'v6' => '',
            ]);

            if ($response->successful()) {
                $raw = $response->json();
                $data = array_change_key_case($raw, CASE_LOWER);

                // Parse values based on new ESP32 code structure
                $temp = (float) ($data['v0'] ?? 0);
                $hum = (float) ($data['v1'] ?? 0);
                $s1 = (int) ($data['v2'] ?? 0);
                $f1 = (isset($data['v3']) && (int) $data['v3'] == 1);
                $s2 = (int) ($data['v4'] ?? 0);
                $s3 = (int) ($data['v5'] ?? 0);
                $f2 = (isset($data['v6']) && (int) $data['v6'] == 1);

                // Aggregate
                $smoke = max($s1, $s2, $s3);
                $fire = ($f1 || $f2);

                SensorLog::create([
                    'temp' => $temp,
                    'hum' => $hum,
                    'smoke' => $smoke,
                    'fire' => $fire,
                    'smoke1' => $s1,
                    'smoke2' => $s2,
                    'smoke3' => $s3,
                    'flame1' => $f1,
                    'flame2' => $f2
                ]);

                $this->info("Record created: T:$temp S1:$s1 S2:$s2 S3:$s3 F:" . ($fire ? '1' : '0'));
            } else {
                $this->error("Gagal connect ke Blynk.");
            }

        } catch (\Exception $e) {
            $this->error("Gagal ambil data: " . $e->getMessage());
        }
    }
}