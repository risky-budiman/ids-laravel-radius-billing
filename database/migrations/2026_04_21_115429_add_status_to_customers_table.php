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
        Schema::table('customers', function (Blueprint $table) {
            // new, waiting_activation, active, suspended, waiting_dismantle, dismantled, canceled
            $table->string('status')->default('new')->after('is_active');
        });

        // Add canceled to tickets status enum if possible, or just handle at model level if DB allows
        // Since I'm using string for status in customers, I'll do the same for flexibility if needed, 
        // but for tickets I used enum in previous step.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
