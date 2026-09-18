<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class DatabaseController extends Controller
{
    /**
     * Export database to a .sql file.
     * Optional query params: start_date, end_date (Y-m-d). Kalau diisi,
     * hanya dump sensor_logs dengan WHERE created_at BETWEEN.
     * Kalau kosong, full backup semua tabel.
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $tag = $start || $end
            ? sprintf('range_%s_to_%s', $start ?: 'BEGIN', $end ?: 'NOW')
            : 'full';

        $filename = sprintf('backup_noc_dashboard_%s_%s.sql', $tag, now()->format('Y-m-d_His'));
        $path     = storage_path('app/' . $filename);

        $host = env('DB_HOST', 'db');
        $user = env('DB_USERNAME', 'noc_user');
        $pass = env('DB_PASSWORD', '');
        $db   = env('DB_DATABASE', 'noc_dashboard');

        $mysqldump = $this->resolveBinary('mysqldump');

        // base flags (single-transaction supaya tidak lock InnoDB, skip-ssl untuk container)
        $baseFlags = [
            '--skip-ssl',
            '--single-transaction',
            '--quick',
            '--default-character-set=utf8mb4',
            '--user=' . escapeshellarg($user),
            '--host=' . escapeshellarg($host),
        ];
        if ($pass !== '') {
            $baseFlags[] = '--password=' . escapeshellarg($pass);
        }

        // Filter rentang waktu -> dump sensor_logs saja
        if ($start || $end) {
            $whereParts = [];
            if ($start) $whereParts[] = "created_at >= '" . $start . " 00:00:00'";
            if ($end)   $whereParts[] = "created_at <= '" . $end   . " 23:59:59'";
            $where = implode(' AND ', $whereParts);

            $cmd = sprintf(
                '%s %s --where=%s %s sensor_logs > %s 2>&1',
                escapeshellarg($mysqldump),
                implode(' ', $baseFlags),
                escapeshellarg($where),
                escapeshellarg($db),
                escapeshellarg($path)
            );
        } else {
            // Full backup
            $cmd = sprintf(
                '%s %s %s > %s 2>&1',
                escapeshellarg($mysqldump),
                implode(' ', $baseFlags),
                escapeshellarg($db),
                escapeshellarg($path)
            );
        }

        try {
            $output = [];
            $retval = null;
            exec($cmd, $output, $retval);

            if ($retval === 0 && file_exists($path) && filesize($path) > 0) {
                return Response::download($path)->deleteFileAfterSend(true);
            }

            $errorMsg = 'Gagal membuat backup. Exit=' . $retval;
            if (!empty($output)) {
                $errorMsg .= ' | ' . implode(' ', array_slice($output, 0, 5));
            }
            if (file_exists($path)) {
                @unlink($path);
            }
            Log::warning('DB export failed: ' . $errorMsg);
            return back()->with('error', $errorMsg);

        } catch (\Throwable $e) {
            if (file_exists($path)) @unlink($path);
            return back()->with('error', 'Export error: ' . $e->getMessage());
        }
    }

    /**
     * Import database from a .sql file.
     * Validasi minimal supaya tidak crash MySQL.
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:65536', // 64 MB
        ]);

        $file = $request->file('backup_file');

        // Validasi sederhana: harus berisi sesuatu yg mirip SQL
        $head = @file_get_contents($file->getRealPath(), false, null, 0, 4096) ?: '';
        if (!preg_match('/(CREATE\s+TABLE|INSERT\s+INTO|DROP\s+TABLE|--\s*MySQL|--\s*MariaDB|MySQL\s*dump)/i', $head)) {
            return back()->with('error', 'File bukan SQL backup yang valid.');
        }

        $stamp = now()->format('Ymd_His');
        $stored = $file->storeAs('backups', "restore_{$stamp}.sql");
        $fullPath = storage_path('app/' . $stored);

        $host = env('DB_HOST', 'db');
        $user = env('DB_USERNAME', 'noc_user');
        $pass = env('DB_PASSWORD', '');
        $db   = env('DB_DATABASE', 'noc_dashboard');

        $mysql = $this->resolveBinary('mysql');

        $flags = [
            '--skip-ssl',
            '--default-character-set=utf8mb4',
            '--user=' . escapeshellarg($user),
            '--host=' . escapeshellarg($host),
        ];
        if ($pass !== '') {
            $flags[] = '--password=' . escapeshellarg($pass);
        }

        $cmd = sprintf(
            '%s %s %s < %s 2>&1',
            escapeshellarg($mysql),
            implode(' ', $flags),
            escapeshellarg($db),
            escapeshellarg($fullPath)
        );

        try {
            $output = [];
            $retval = null;
            exec($cmd, $output, $retval);

            Storage::delete($stored);

            if ($retval !== 0) {
                $msg = 'Restore gagal (exit=' . $retval . ')';
                if (!empty($output)) {
                    $msg .= ': ' . implode(' | ', array_slice($output, 0, 3));
                }
                Log::warning('DB restore failed: ' . $msg);
                return back()->with('error', $msg);
            }

            // Verifikasi cepat — pastikan tabel sensor_logs masih ada
            try {
                $count = DB::table('sensor_logs')->count();
            } catch (\Throwable $e) {
                return back()->with('error', 'Restore selesai tapi tabel sensor_logs tidak terbaca: ' . $e->getMessage());
            }

            return back()->with('success', "Database berhasil di-restore. sensor_logs sekarang: {$count} baris.");

        } catch (\Throwable $e) {
            if (file_exists($fullPath)) @unlink($fullPath);
            return back()->with('error', 'Restore error: ' . $e->getMessage());
        }
    }

    /**
     * Cari biner mysql / mysqldump. Di Linux container ambil dari PATH,
     * di Windows (laragon) cari di C:\laragon\bin\mysql\mysql-*\bin.
     */
    private function resolveBinary(string $name): string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if (!$isWindows) {
            return $name; // PATH (Linux container)
        }
        $exe = $name . '.exe';
        $laragonRoot = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonRoot)) {
            $dirs = glob($laragonRoot . '\\mysql-*');
            if (!empty($dirs)) {
                $candidate = $dirs[0] . '\\bin\\' . $exe;
                if (file_exists($candidate)) return $candidate;
            }
        }
        return $name;
    }
}
