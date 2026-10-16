<?php

namespace App\Http\Controllers;

use App\Console\Commands\ServerCheckReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServerCheckController extends Controller
{
    /**
     * GET /api/server-check/status
     * Return flag himbauan + metadata.
     */
    public function status()
    {
        $data = Cache::get(ServerCheckReminder::CACHE_KEY);
        return response()->json([
            'active'   => (bool) $data,
            'payload'  => $data,
            'ack_by'   => Cache::get('noc.server_check.ack_by'),
            'ack_at'   => Cache::get('noc.server_check.ack_at'),
            'now'      => now()->toIso8601String(),
        ]);
    }

    /**
     * POST /api/server-check/ack
     * User (admin/superadmin) acknowledge sudah mengecek server.
     * Clear flag + simpan siapa+kapan yg acknowledge + SET acked_today
     * (supaya command tidak re-trigger panel hari ini).
     */
    public function acknowledge(Request $request)
    {
        $user = $request->user();
        $wasActive = (bool) Cache::get(ServerCheckReminder::CACHE_KEY);

        // Clear flag himbauan
        Cache::forget(ServerCheckReminder::CACHE_KEY);

        $ttlToMidnight = now()->endOfDay()->diffInSeconds(now());

        // Simpan info ack
        Cache::put('noc.server_check.ack_by', $user->name ?? $user->username ?? 'unknown', $ttlToMidnight);
        Cache::put('noc.server_check.ack_at', now()->toIso8601String(), $ttlToMidnight);

        // Flag "sudah di-ack hari ini" — command akan SKIP walau jalan lagi
        Cache::put('noc.server_check.acked_today', true, $ttlToMidnight);

        // Opsional: kirim notif Telegram "server sudah dicek oleh X"
        $this->notifyAck($user);

        return response()->json([
            'status'      => 'ok',
            'was_active'  => $wasActive,
            'ack_by'      => $user->name ?? $user->username,
            'ack_at'      => now()->toIso8601String(),
        ]);
    }

    private function notifyAck($user): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) return;

        $name = $user->name ?? $user->username ?? 'unknown';
        $msg = sprintf(
            "✅ *SERVER SUDAH DICEK*\n\n" .
            "Oleh: %s\nWaktu: %s\n\nTerima kasih atas konfirmasinya.",
            $name,
            now()->format('d M Y H:i:s')
        );

        try {
            \Illuminate\Support\Facades\Http::timeout(5)->retry(2, 300)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $msg,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ServerCheck ack Telegram fail: ' . $e->getMessage());
        }
    }
}
