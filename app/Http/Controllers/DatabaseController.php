<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class DatabaseController extends Controller
{
    /**
     * Export database to a .sql file
     */
    public function export()
    {
        $filename = "backup_noc_dashboard_" . now()->format('Y-m-d_His') . ".sql";
        $path = storage_path('app/' . $filename);

        // Get DB credentials from env
        $host = env('DB_HOST', 'db');
        $user = env('DB_USERNAME', 'noc_user');
        $pass = env('DB_PASSWORD', '');
        $db = env('DB_DATABASE', 'noc_dashboard');

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $mysqldump = 'mysqldump';

        if ($isWindows) {
            $laragonMysqlPath = 'C:\laragon\bin\mysql';
            if (file_exists($laragonMysqlPath)) {
                $dirs = glob($laragonMysqlPath . '\mysql-*');
                if (!empty($dirs)) {
                    $mysqldump = $dirs[0] . '\bin\mysqldump.exe';
                }
            }
        }

        // Handle empty password carefully
        $passwordFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";

        // Command to run
        $command = "\"{$mysqldump}\" --ssl-mode=DISABLED --user=\"{$user}\" {$passwordFlag} --host=\"{$host}\" \"{$db}\" > \"{$path}\"";

        try {
            system($command);

            if (file_exists($path) && filesize($path) > 0) {
                return Response::download($path)->deleteFileAfterSend(true);
            } else {
                return back()->with('error', 'Gagal membuat file backup. Pastikan mysql-client terinstall dan database berjalan.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Import database from a .sql file
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file'
        ]);

        $file = $request->file('backup_file');
        $path = $file->storeAs('backups', 'restore_temp.sql');
        $fullPath = storage_path('app/' . $path);

        $host = env('DB_HOST', 'db');
        $user = env('DB_USERNAME', 'noc_user');
        $pass = env('DB_PASSWORD', '');
        $db = env('DB_DATABASE', 'noc_dashboard');

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $mysql = 'mysql';

        if ($isWindows) {
            $laragonMysqlPath = 'C:\laragon\bin\mysql';
            if (file_exists($laragonMysqlPath)) {
                $dirs = glob($laragonMysqlPath . '\mysql-*');
                if (!empty($dirs)) {
                    $mysql = $dirs[0] . '\bin\mysql.exe';
                }
            }
        }

        // Handle empty password carefully
        $passwordFlag = !empty($pass) ? "--password=\"{$pass}\"" : "";

        // Command to restore
        $command = "\"{$mysql}\" --ssl-mode=DISABLED --user=\"{$user}\" {$passwordFlag} --host=\"{$host}\" \"{$db}\" < \"{$fullPath}\"";

        try {
            system($command);
            Storage::delete($path);
            return back()->with('success', 'Database berhasil di-restore!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal me-restore database: ' . $e->getMessage());
        }
    }
}
