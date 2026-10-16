<?php

namespace App\Console\Commands;

use App\Console\Commands\ServerCheckReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Debug & kontrol state server-check reminder.
 */
class ServerCheckStatus extends Command
{
    protected $signature = 'noc:server-check
                            {action=status : status | clear | ack-reset}
                            {--who= : Nama user utk dummy ack (dipakai sama action=status)}';

    protected $description = 'Debug server-check reminder: status | clear (hapus panel) | ack-reset (hapus semua state hari ini).';

    public function handle(): int
    {
        $action = $this->argument('action');

        if ($action === 'clear') {
            Cache::forget(ServerCheckReminder::CACHE_KEY);
            $this->info('✓ Flag himbauan di-clear. Panel di dashboard akan hilang next poll (~60s).');
            return self::SUCCESS;
        }

        if ($action === 'ack-reset') {
            Cache::forget(ServerCheckReminder::CACHE_KEY);
            Cache::forget('noc.server_check.acked_today');
            Cache::forget('noc.server_check.ack_by');
            Cache::forget('noc.server_check.ack_at');
            $this->info('✓ Semua state server-check direset. Reminder bisa trigger ulang.');
            return self::SUCCESS;
        }

        // Default: status
        $reminder   = Cache::get(ServerCheckReminder::CACHE_KEY);
        $ackedToday = Cache::get('noc.server_check.acked_today');
        $ackBy      = Cache::get('noc.server_check.ack_by');
        $ackAt      = Cache::get('noc.server_check.ack_at');

        $this->line('');
        $this->info('===== SERVER-CHECK STATE =====');
        $this->table(['Key', 'Value'], [
            ['Reminder active',       $reminder ? 'YES' : 'no'],
            ['  triggered_at',        $reminder['triggered_at'] ?? '-'],
            ['  motion_count',        $reminder['motion_count'] ?? '-'],
            ['  reason',              $reminder['reason'] ?? '-'],
            ['Acked today',           $ackedToday ? 'YES' : 'no'],
            ['Ack by',                $ackBy ?: '-'],
            ['Ack at',                $ackAt ?: '-'],
        ]);
        $this->line('');
        $this->line('Perintah lain:');
        $this->line('  php artisan noc:server-check clear       → hapus panel saja');
        $this->line('  php artisan noc:server-check ack-reset   → reset total (bisa trigger ulang)');
        $this->line('  php artisan noc:server-check-reminder --force   → force trigger (test)');

        return self::SUCCESS;
    }
}
