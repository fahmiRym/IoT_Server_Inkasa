<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorLog;
use Carbon\Carbon;

class SensorLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        SensorLog::truncate();

        $now = Carbon::now();
        
        for ($i = 60; $i >= 0; $i--) {
            SensorLog::create([
                'temp' => rand(220, 280) / 10, // 22.0 - 28.0
                'hum' => rand(400, 600) / 10,  // 40.0 - 60.0
                'smoke' => rand(100, 500),      // 100 - 500 PPM
                'created_at' => $now->copy()->subMinutes($i),
                'updated_at' => $now->copy()->subMinutes($i),
            ]);
        }
    }
}
