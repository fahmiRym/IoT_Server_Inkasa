<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Sensor ingest — ESP32 push tiap ~2 detik = ~30/menit.
        // Beri buffer 120/menit per IP + per API key (agar 1 IP nakal tidak spam).
        RateLimiter::for('sensor-ingest', function (Request $request) {
            return [
                Limit::perMinute(120)->by($request->ip()),
                Limit::perMinute(180)->by((string) $request->input('api_key', 'anon')),
            ];
        });

        // Login attempt — 6/menit per IP + username.
        RateLimiter::for('login', function (Request $request) {
            $ident = strtolower((string) $request->input('username', $request->input('email', '')));
            return [
                Limit::perMinute(6)->by($request->ip()),
                Limit::perMinute(6)->by($ident.'|'.$request->ip()),
            ];
        });

        // OTP resend — 3/menit per session (hindari spam email).
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->session()->getId() ?: $request->ip());
        });
    }
}
