<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename role 'operator' -> 'admin'.
 * Strategi kompatibel MySQL + SQLite:
 *  1. Expand enum sementara jadi ['superadmin', 'operator', 'admin']
 *  2. UPDATE users SET role='admin' WHERE role='operator'
 *  3. Shrink enum ke ['superadmin', 'admin'] + default 'admin'
 * Untuk SQLite (dev lokal) yang tidak support ALTER COLUMN native, cukup UPDATE row-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Step 1: perluas enum sementara
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','operator','admin') NOT NULL DEFAULT 'operator'");
            // Step 2: pindah semua operator -> admin
            DB::table('users')->where('role', 'operator')->update(['role' => 'admin']);
            // Step 3: enum final tanpa 'operator', default 'admin'
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin') NOT NULL DEFAULT 'admin'");
        } else {
            // SQLite / lainnya: tidak ada enum, cukup UPDATE data.
            DB::table('users')->where('role', 'operator')->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','operator','admin') NOT NULL DEFAULT 'admin'");
            DB::table('users')->where('role', 'admin')->update(['role' => 'operator']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','operator') NOT NULL DEFAULT 'operator'");
        } else {
            DB::table('users')->where('role', 'admin')->update(['role' => 'operator']);
        }
    }
};
