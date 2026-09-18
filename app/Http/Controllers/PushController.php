<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushController extends Controller
{
    /**
     * Return VAPID public key (frontend butuh untuk subscribe).
     * Set VAPID_PUBLIC_KEY di .env. Generate via `web-push generate-vapid-keys`
     * atau `php artisan noc:generate-vapid` (kalau nanti dibuat command-nya).
     */
    public function vapidKey()
    {
        return response()->json([
            'publicKey' => config('services.push.vapid_public') ?: null,
        ]);
    }

    /**
     * Simpan subscription baru dari browser.
     * Payload = PushSubscription JSON:
     *   { endpoint, keys: { p256dh, auth }, expirationTime }
     */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint'  => 'required|url|max:500',
            'keys.p256dh' => 'required|string|max:200',
            'keys.auth'   => 'required|string|max:100',
        ]);

        DB::table('push_subscriptions')->updateOrInsert(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'      => auth()->id(),
                'p256dh'       => $data['keys']['p256dh'],
                'auth'         => $data['keys']['auth'],
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
                'last_used_at' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Hapus subscription (unsubscribe dari device tsb).
     */
    public function unsubscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|url|max:500',
        ]);
        DB::table('push_subscriptions')
            ->where('endpoint', $data['endpoint'])
            ->where('user_id', auth()->id())
            ->delete();
        return response()->json(['status' => 'ok']);
    }
}
