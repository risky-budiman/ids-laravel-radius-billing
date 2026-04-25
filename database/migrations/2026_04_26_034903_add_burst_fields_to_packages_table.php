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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('download_speed')->nullable()->change();
            $table->string('upload_speed')->nullable()->change();
            
            // Burst settings
            $table->string('burst_limit')->nullable();
            $table->string('burst_threshold')->nullable();
            $table->string('burst_time')->nullable();
            $table->string('limit_at')->nullable();
            $table->integer('priority')->default(8);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Revert types if needed, but sqlite/others might have issues with change() in down
            // For simplicity in this dev environment:
            $table->dropColumn(['burst_limit', 'burst_threshold', 'burst_time', 'limit_at', 'priority']);
        });
    }
};
