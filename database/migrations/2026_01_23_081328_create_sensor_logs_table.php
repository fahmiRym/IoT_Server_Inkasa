<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('sensor_logs', function (Blueprint $table) {
        $table->id();
        $table->float('temp');      // Kolom Suhu
        $table->float('hum');       // Kolom Kelembaban
        $table->integer('smoke');   // Kolom Asap
        $table->timestamps();       // Otomatis catat waktu (created_at)
    });
}

    /**
     * Reverse the migrations.
     */ 
    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};
