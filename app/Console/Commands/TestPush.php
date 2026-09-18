<?php

namespace App\Console\Commands;

use App\Services\NocPushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestPush extends Command
{
    protected $signature = 'noc:test-push
                            {--danger : Kirim sebagai severity danger (default: info)}
                            {--title=NOC Test Push : Judul notif}
                            {--body= : Body notif (default: pesan test standar)}';

    protected $description = 'Kirim push notification test ke semua device yg subscribe.';

    public function handle(NocPushSender $sender): int
    {
        $count = DB::table('push_subscriptions')->count();
        if ($count === 0) {
            $this->warn('Tidak ada device yang subscribe push. Buka /devices di browser & aktifkan "AKTIFKAN NOTIFIKASI".');
            return self::SUCCESS;
        }

        $pub = config('services.push.vapid_public');
        if (!$pub) {
            $this->error('VAPID_PUBLIC_KEY belum di-set di .env. Jalankan: php artisan noc:generate-vapid --append');
            return self::FAILURE;
        }

        $sent = $sender->broadcast([
            'title'    => $this->option('title'),
            'body'     => $this->option('body') ?: '✓ Notif push berjalan · ' . now()->format('d M Y H:i:s'),
            'severity' => $this->option('danger') ? 'danger' : 'info',
            'url'      => '/',
            'tag'      => 'noc-test',
        ]);

        $this->info("→ Push dikirim ke {$sent}/{$count} device.");
        return self::SUCCESS;
    }
}
