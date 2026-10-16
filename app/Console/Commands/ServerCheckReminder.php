<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jalankan tiap jam 15:00 WIB (atau sesuai NOC_CHECK_HOUR):
 * Cek motion-detector events hari ini. Kalau TIDAK ada motion sejak jam 00:00,
 * kirim himbauan ke Telegram + set flag di cache supaya dashboard tampilkan panel.
 *
 * Flag reset otomatis saat ada motion baru (via `motion:ack`) atau tengah malam.
 */
class ServerCheckReminder extends Command
{
    protected $signature = 'noc:server-check-reminder
                            {--force : Paksa kirim walau ada motion}
                            {--path= : Path dir motion clips (default storage/app/motion)}';

    protected $description = 'Himbauan cek server lewat Telegram kalau tidak ada motion CCTV sejak pagi.';

    public const CACHE_KEY = 'noc.server_check.reminder';

    public function handle(): int
    {
        $dir = $this->option('path') ?: storage_path('app/motion');
        $motionCount = $this->countTodayMotion($dir);

        $this->info("Motion events hari ini: {$motionCount} file di {$dir}");

        // Kalau user sudah ack hari ini, SKIP (kecuali --force)
        if (!$this->option('force') && Cache::get('noc.server_check.acked_today')) {
            $this->info('✓ Server sudah di-ack oleh admin hari ini — skip reminder.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && $motionCount > 0) {
            $this->info('✓ Ada aktivitas CCTV — tidak perlu himbauan.');
            // Clear reminder kalau tiba-tiba ada motion
            Cache::forget(self::CACHE_KEY);
            return self::SUCCESS;
        }

        // Set cache flag — dashboard akan tampilkan panel sampai di-ack atau tengah malam
        $payload = [
            'triggered_at'  => now()->toIso8601String(),
            'motion_count'  => $motionCount,
            'motion_dir'    => $dir,
            'window_start'  => now()->startOfDay()->toIso8601String(),
            'reason'        => $motionCount === 0
                ? 'Tidak ada aktivitas motion sejak pagi'
                : 'Dikirim paksa oleh admin',
        ];

        // Expire tengah malam (otomatis reset keesokan harinya)
        $ttl = now()->endOfDay()->diffInSeconds(now());
        Cache::put(self::CACHE_KEY, $payload, $ttl);

        $this->sendTelegram($motionCount);
        $this->info('✓ Himbauan dikirim ke Telegram + flag dashboard di-set.');

        return self::SUCCESS;
    }

    private function countTodayMotion(string $dir): int
    {
        if (!File::isDirectory($dir)) return 0;

        $start = now()->startOfDay()->getTimestamp();
        $count = 0;
        foreach (File::allFiles($dir) as $f) {
            if (!preg_match('/\.(mp4|jpg|jpeg|png)$/i', $f->getFilename())) continue;
            if ($f->getMTime() >= $start) $count++;
        }
        return $count;
    }

    private function sendTelegram(int $motionCount): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) {
            Log::warning('ServerCheckReminder: Telegram token/chat_id belum di-set.');
            return;
        }

        $msg = sprintf(
            "⚠️ *HIMBAUAN CEK SERVER NOC*\n\n" .
            "Hingga %s belum terdeteksi aktivitas apapun di ruang server (CCTV motion detector).\n\n" .
            "Mohon lakukan pengecekan fisik:\n" .
            "• Suhu & ventilasi\n" .
            "• Lampu indikator rack\n" .
            "• Kondisi UPS & jaringan\n" .
            "• Pastikan pintu aman\n\n" .
            "Setelah dicek, buka dashboard & klik tombol *\"Sudah Dicek\"* untuk acknowledgement.\n\n" .
            "_Motion events hari ini: %d_",
            now()->format('H:i'),
            $motionCount
        );

        try {
            Http::timeout(8)->retry(2, 300)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $msg,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (\Throwable $e) {
            Log::warning('ServerCheckReminder Telegram send failed: ' . $e->getMessage());
        }
    }
}
