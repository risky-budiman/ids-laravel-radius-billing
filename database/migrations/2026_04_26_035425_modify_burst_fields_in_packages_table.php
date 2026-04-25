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
            // Remove the previous combined columns
            if (Schema::hasColumn('packages', 'burst_limit')) {
                $table->dropColumn(['burst_limit', 'burst_threshold', 'burst_time', 'limit_at']);
            }
            
            // Add separate upload/download columns
            $table->string('burst_limit_up')->nullable();
            $table->string('burst_limit_down')->nullable();
            
            $table->string('burst_threshold_up')->nullable();
            $table->string('burst_threshold_down')->nullable();
            
            $table->string('burst_time_up')->nullable();
            $table->string('burst_time_down')->nullable();
            
            $table->string('limit_at_up')->nullable();
            $table->string('limit_at_down')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'burst_limit_up', 'burst_limit_down',
                'burst_threshold_up', 'burst_threshold_down',
                'burst_time_up', 'burst_time_down',
                'limit_at_up', 'limit_at_down'
            ]);
            
            $table->string('burst_limit')->nullable();
            $table->string('burst_threshold')->nullable();
            $table->string('burst_time')->nullable();
            $table->string('limit_at')->nullable();
        });
    }
};
