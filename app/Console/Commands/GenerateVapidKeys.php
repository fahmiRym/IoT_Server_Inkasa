<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'noc:generate-vapid
                            {--append : Append/replace ke .env otomatis (default hanya print)}
                            {--subject= : mailto:xxx atau URL admin (default dari .env atau it@inkalum.com)}';

    protected $description = 'Generate VAPID public/private key pair untuk Web Push (P-256 EC).';

    public function handle(): int
    {
        try {
            if (class_exists(VAPID::class)) {
                // Prefer library web-push (lebih reliable di Windows tanpa openssl.cnf)
                $keys = VAPID::createVapidKeys();
                $publicB64  = $keys['publicKey'];
                $privateB64 = $keys['privateKey'];
                $method = 'minishlink/web-push VAPID::createVapidKeys()';
            } else {
                [$publicB64, $privateB64] = $this->openSslFallback();
                $method = 'openssl (fallback)';
            }
        } catch (\Throwable $e) {
            // Kalau library gagal (unlikely), coba openssl fallback
            $this->warn('Library web-push gagal: ' . $e->getMessage() . ' — fallback ke openssl');
            try {
                [$publicB64, $privateB64] = $this->openSslFallback();
                $method = 'openssl (fallback)';
            } catch (\Throwable $e2) {
                $this->error('Semua metode gagal: ' . $e2->getMessage());
                $this->line('Tip: pastikan extension openssl aktif + install minishlink/web-push package.');
                return self::FAILURE;
            }
        }

        $subject = $this->option('subject')
            ?: env('VAPID_SUBJECT')
            ?: 'mailto:it.inkasajayaaluminium@gmail.com';

        $this->info("✓ VAPID key pair generated ({$method})");
        $this->line('');
        $this->line('  VAPID_PUBLIC_KEY='  . $publicB64);
        $this->line('  VAPID_PRIVATE_KEY=' . $privateB64);
        $this->line('  VAPID_SUBJECT='     . $subject);
        $this->line('');

        if ($this->option('append')) {
            $envPath = base_path('.env');
            if (!is_writable($envPath)) {
                $this->warn('.env tidak writable — copy manual key di atas ke .env');
                return self::SUCCESS;
            }
            $env = file_get_contents($envPath);
            $lines = [
                'VAPID_PUBLIC_KEY'  => $publicB64,
                'VAPID_PRIVATE_KEY' => $privateB64,
                'VAPID_SUBJECT'     => $subject,
            ];
            foreach ($lines as $k => $v) {
                if (preg_match("/^{$k}=.*/m", $env)) {
                    $env = preg_replace("/^{$k}=.*/m", "{$k}={$v}", $env);
                } else {
                    $env = rtrim($env) . "\n{$k}={$v}\n";
                }
            }
            file_put_contents($envPath, $env);
            $this->info('✓ Ditulis ke .env — jalankan `php artisan config:clear`');
        } else {
            $this->line('Tip: jalankan dgn `--append` untuk auto-tulis ke .env');
        }

        return self::SUCCESS;
    }

    /**
     * Fallback pakai openssl PHP native.
     * Di Windows/Laragon, openssl butuh openssl.cnf. Kita cari di beberapa lokasi umum.
     */
    private function openSslFallback(): array
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException('Extension openssl belum aktif.');
        }

        $config = [
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        // Cari openssl.cnf otomatis (Windows Laragon)
        $possibleCnf = [
            getenv('OPENSSL_CONF'),
            dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
            dirname(PHP_BINARY) . '/extras/openssl.cnf',
            'C:/laragon/bin/php/php-' . phpversion() . '-Win32-vs16-x64/extras/ssl/openssl.cnf',
        ];
        foreach ($possibleCnf as $cnf) {
            if ($cnf && file_exists($cnf)) {
                $config['config'] = $cnf;
                break;
            }
        }

        $res = openssl_pkey_new($config);
        if (!$res) {
            $err = '';
            while ($e = openssl_error_string()) $err .= $e . '; ';
            throw new \RuntimeException('openssl_pkey_new gagal: ' . $err);
        }

        $details = openssl_pkey_get_details($res);
        if (!$details || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            throw new \RuntimeException('Gagal ambil detail EC key.');
        }

        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $publicRaw = "\x04" . $x . $y;
        $d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        return [
            $this->b64UrlSafe($publicRaw),
            $this->b64UrlSafe($d),
        ];
    }

    private function b64UrlSafe(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
