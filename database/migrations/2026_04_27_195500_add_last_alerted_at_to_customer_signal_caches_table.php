<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_signal_caches', function (Blueprint $table) {
            $table->timestamp('last_alerted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_signal_caches', function (Blueprint $table) {
            $table->dropColumn('last_alerted_at');
        });
    }
};
