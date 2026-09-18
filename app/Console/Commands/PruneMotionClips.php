<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneMotionClips extends Command
{
    protected $signature = 'motion:prune
                            {--days=7 : Umur maksimal klip motion (hari)}
                            {--path= : Direktori klip motion (default storage/app/motion)}';

    protected $description = 'Hapus klip motion detector (.mp4/.jpg) yang lebih tua dari N hari, cegah storage penuh.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dir  = $this->option('path') ?: storage_path('app/motion');

        if (!File::isDirectory($dir)) {
            $this->warn("Direktori tidak ada: {$dir}");
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;
        $freedBytes = 0;

        foreach (File::allFiles($dir) as $f) {
            $name = strtolower($f->getFilename());
            if (!preg_match('/\.(mp4|jpg|jpeg|png)$/', $name)) continue;
            if ($f->getMTime() >= $cutoff) continue;

            $freedBytes += $f->getSize();
            @unlink($f->getRealPath());
            $deleted++;
        }

        $mb = round($freedBytes / 1024 / 1024, 2);
        $this->info("✓ prune motion: {$deleted} file dihapus ({$mb} MB dibebaskan), umur > {$days} hari");

        return self::SUCCESS;
    }
}
