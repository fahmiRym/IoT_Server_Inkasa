<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class NocPushSender
{
    /**
     * Kirim push notification ke semua subscription aktif.
     *
     * @param array $payload = [
     *     'title'    => string,
     *     'body'     => string,
     *     'severity' => 'danger'|'warning'|'info',
     *     'url'      => string (optional),
     *     'tag'      => string (optional, deduplicate),
     * ]
     * @return int  jumlah subscription yang berhasil dikirim
     */
    public function broadcast(array $payload): int
    {
        $pub  = config('services.push.vapid_public');
        $priv = config('services.push.vapid_private');
        $subj = config('services.push.vapid_subject');

        if (!$pub || !$priv) {
            Log::warning('Push broadcast skipped: VAPID keys belum di-set.');
            return 0;
        }
        if (!class_exists(WebPush::class)) {
            Log::warning('Push broadcast skipped: minishlink/web-push belum diinstall.');
            return 0;
        }

        $subs = DB::table('push_subscriptions')->get();
        if ($subs->isEmpty()) return 0;

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => $subj ?: 'mailto:noc@inkalum.com',
                    'publicKey'  => $pub,
                    'privateKey' => $priv,
                ],
            ], [], 10);
        } catch (\Throwable $e) {
            Log::error('WebPush init fail: ' . $e->getMessage());
            return 0;
        }

        $body = json_encode([
            'title'    => $payload['title'] ?? 'NOC Alert',
            'body'     => $payload['body']  ?? '',
            'severity' => $payload['severity'] ?? 'info',
            'url'      => $payload['url']   ?? '/',
            'tag'      => $payload['tag']   ?? 'noc-alert',
        ]);

        foreach ($subs as $s) {
            try {
                $sub = Subscription::create([
                    'endpoint'        => $s->endpoint,
                    'publicKey'       => $s->p256dh,
                    'authToken'       => $s->auth,
                    'contentEncoding' => 'aesgcm',
                ]);
                $webPush->queueNotification($sub, $body);
            } catch (\Throwable $e) {
                Log::warning('Push queue fail: ' . $e->getMessage());
            }
        }

        $ok = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $ok++;
                DB::table('push_subscriptions')
                    ->where('endpoint', $endpoint)
                    ->update(['last_used_at' => now()]);
            } else {
                // 404/410 = subscription expired -> hapus
                $status = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
                if (in_array($status, [404, 410], true)) {
                    DB::table('push_subscriptions')->where('endpoint', $endpoint)->delete();
                    Log::info("Push subscription expired ({$status}), removed: " . substr($endpoint, 0, 60));
                } else {
                    Log::warning('Push send fail: ' . $report->getReason() . ' (endpoint: ' . substr($endpoint, 0, 60) . ')');
                }
            }
        }

        return $ok;
    }
}
