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
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->integer('smoke1')->nullable()->after('smoke');
            $table->integer('smoke2')->nullable()->after('smoke1');
            $table->integer('smoke3')->nullable()->after('smoke2');
            $table->boolean('flame1')->default(false)->after('fire');
            $table->boolean('flame2')->default(false)->after('flame1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->dropColumn(['smoke1', 'smoke2', 'smoke3', 'flame1', 'flame2']);
        });
    }
};
