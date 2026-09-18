<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
                            {--keep=14 : Jumlah backup terbaru yang disimpan (default 14 hari)}
                            {--path= : Direktori tujuan (default storage/app/backups)}';

    protected $description = 'Dump seluruh database via mysqldump ke storage/app/backups + rotasi otomatis.';

    public function handle(): int
    {
        $conn = config('database.default');
        $db   = config("database.connections.$conn");

        if (($db['driver'] ?? '') !== 'mysql') {
            $this->error("Driver DB '{$db['driver']}' belum didukung, cuma mysql.");
            return self::FAILURE;
        }

        $dir = $this->option('path') ?: storage_path('app/backups');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ts   = now()->format('Ymd_His');
        $file = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . "backup_{$db['database']}_{$ts}.sql";

        // Pilih binary sesuai OS
        $bin = PHP_OS_FAMILY === 'Windows' ? 'mysqldump.exe' : 'mysqldump';

        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s %s --single-transaction --quick --routines --triggers %s > %s',
            escapeshellcmd($bin),
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg((string) $db['username']),
            $db['password'] !== '' ? '--password=' . escapeshellarg((string) $db['password']) : '',
            escapeshellarg((string) $db['database']),
            escapeshellarg($file)
        );

        $this->info("→ dump ke {$file}");
        $out = null; $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);

        if ($ret !== 0 || !File::exists($file) || File::size($file) < 512) {
            Log::error('db:backup gagal', ['ret' => $ret, 'out' => $out]);
            $this->error("mysqldump gagal (exit={$ret}). Cek log.");
            @unlink($file);
            return self::FAILURE;
        }

        $sizeMb = round(File::size($file) / 1024 / 1024, 2);
        $this->info("✓ backup selesai ({$sizeMb} MB)");

        // Rotasi: hapus backup terlama jika melewati --keep
        $keep = max(1, (int) $this->option('keep'));
        $files = collect(File::files($dir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sql'))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->values();

        if ($files->count() > $keep) {
            foreach ($files->slice($keep) as $old) {
                @unlink($old->getRealPath());
                $this->line("  · prune " . $old->getFilename());
            }
        }

        return self::SUCCESS;
    }
}
