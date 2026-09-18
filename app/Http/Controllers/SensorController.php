<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SensorLog;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        $configuredKey = (string) config('services.sensor.api_key', '');
        $providedKey   = (string) $request->input('api_key', '');

        // Constant-time comparison biar tahan timing attack.
        if ($configuredKey === '' || !hash_equals($configuredKey, $providedKey)) {
            Log::warning('Sensor ingest 401', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'given_len'  => strlen($providedKey),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid API Key',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'temp'   => 'required|numeric|between:-40,125',
                'hum'    => 'required|numeric|between:0,100',
                'smoke1' => 'nullable|integer|between:0,4095',
                'smoke2' => 'nullable|integer|between:0,4095',
                'smoke3' => 'nullable|integer|between:0,4095',
                'flame1' => 'nullable|boolean',
                'flame2' => 'nullable|boolean',
                'smoke'  => 'nullable|integer|between:0,4095',
                'fire'   => 'nullable|boolean',
            ]);

            $smoke1 = (int) ($validated['smoke1'] ?? 0);
            $smoke2 = (int) ($validated['smoke2'] ?? 0);
            $smoke3 = (int) ($validated['smoke3'] ?? 0);
            $flame1 = (bool) ($validated['flame1'] ?? false);
            $flame2 = (bool) ($validated['flame2'] ?? false);

            $smoke = (int) ($validated['smoke'] ?? max($smoke1, $smoke2, $smoke3));
            $fire  = (bool) ($validated['fire']  ?? ($flame1 || $flame2));

            $data = SensorLog::create([
                'temp'   => (float) $validated['temp'],
                'hum'    => (float) $validated['hum'],
                'smoke'  => $smoke,
                'fire'   => $fire,
                'smoke1' => $smoke1,
                'smoke2' => $smoke2,
                'smoke3' => $smoke3,
                'flame1' => $flame1,
                'flame2' => $flame2,
            ]);

            // Invalidate cache dashboard biar data baru langsung ke-render.
            Cache::forget('latest_sensor_reading');

            // Telegram alert dipusatkan di backend (bukan lagi di ESP32).
            // Hanya dikirim saat transisi state NORMAL <-> DANGER (debounce 2 sample).
            $this->maybeNotifyTelegram([
                'temp'  => (float) $validated['temp'],
                'hum'   => (float) $validated['hum'],
                'smoke' => $smoke,
                'fire'  => $fire,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data saved successfully',
                'data'    => $data,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Sensor store failed: ' . $e->getMessage(), ['ip' => $request->ip()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal error',
            ], 500);
        }
    }

    private function maybeNotifyTelegram(array $s): void
    {
        $tempLimit  = (float) config('services.sensor.temp_limit', 35);
        $smokeLimit = (int)   config('services.sensor.smoke_limit', 600);

        $danger = ($s['temp'] > $tempLimit)
               || ($s['smoke'] > $smokeLimit)
               || $s['fire'];

        // Debounce: butuh 2 sample berurutan sebelum trigger.
        $streakKey = 'sensor.alarm.streak';
        $streak    = Cache::get($streakKey, ['danger' => 0, 'safe' => 0]);

        if ($danger) {
            $streak['danger']++;
            $streak['safe'] = 0;
        } else {
            $streak['safe']++;
            $streak['danger'] = 0;
        }
        Cache::put($streakKey, $streak, now()->addMinutes(10));

        $lastState = (bool) Cache::get('sensor.alarm.state', false);
        $newState  = $lastState;

        if (!$lastState && $streak['danger'] >= 2) {
            $newState = true;
        }
        if ($lastState && $streak['safe'] >= 2) {
            $newState = false;
        }

        if ($newState === $lastState) {
            return;
        }

        Cache::put('sensor.alarm.state', $newState, now()->addDay());

        $msg = $newState
            ? sprintf(
                "🚨 *ALERT KEBAKARAN!*\nTemp: %.1f °C\nHum: %.1f %%\nSmoke: %d\nFlame: %s\nWaktu: %s",
                $s['temp'], $s['hum'], $s['smoke'],
                $s['fire'] ? 'YES' : 'NO',
                now()->format('Y-m-d H:i:s')
            )
            : sprintf(
                "✅ *Kondisi NORMAL kembali*\nTemp: %.1f °C\nHum: %.1f %%\nSmoke: %d\nWaktu: %s",
                $s['temp'], $s['hum'], $s['smoke'],
                now()->format('Y-m-d H:i:s')
            );

        $this->sendTelegram($msg);

        // Web Push broadcast — ke semua device yg subscribe (opsional, silent fail kalau VAPID belum di-set)
        $this->sendWebPush($newState, $s);
    }

    private function sendWebPush(bool $isDanger, array $s): void
    {
        try {
            $sender = app(\App\Services\NocPushSender::class);
            if ($isDanger) {
                $reason = [];
                if ($s['fire']) $reason[] = 'FIRE';
                if ($s['temp'] > (float) config('services.sensor.temp_limit', 35)) $reason[] = sprintf('Suhu %.1f°C', $s['temp']);
                if ($s['smoke'] > (int) config('services.sensor.smoke_limit', 600)) $reason[] = sprintf('Asap %d PPM', $s['smoke']);

                $sender->broadcast([
                    'title'    => '🚨 ALARM: NOC Command Center',
                    'body'     => 'Anomali terdeteksi: ' . (implode(' · ', $reason) ?: 'critical value'),
                    'severity' => 'danger',
                    'url'      => '/',
                    'tag'      => 'noc-alarm',
                ]);
            } else {
                $sender->broadcast([
                    'title'    => '✅ NOC Kembali NORMAL',
                    'body'     => sprintf('Semua parameter aman · %.1f°C · %d PPM', $s['temp'], $s['smoke']),
                    'severity' => 'info',
                    'url'      => '/',
                    'tag'      => 'noc-normal',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Web Push broadcast fail: ' . $e->getMessage());
        }
    }

    private function sendTelegram(string $text): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (!$token || !$chatId) {
            Log::warning('Telegram skipped: bot_token / chat_id belum di-set.');
            return;
        }

        try {
            $resp = Http::timeout(5)
                ->retry(2, 250)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'Markdown',
                ]);

            if (!$resp->ok()) {
                Log::warning('Telegram non-OK response: ' . $resp->status() . ' ' . $resp->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed: ' . $e->getMessage());
        }
    }
}
