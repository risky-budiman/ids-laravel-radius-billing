<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Table for storing OLT status history
        Schema::create('olt_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->boolean('is_online')->default(false);
            $table->json('cpu_usage')->nullable();
            $table->json('memory_usage')->nullable();
            $table->timestamp('last_polled_at');
            $table->timestamps();
        });

        // 2. Table for storing Customer Signal (RX Power) Cache
        Schema::create('customer_signal_caches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('onu_index')->index(); // SNMP Index
            $table->decimal('rx_power', 8, 2)->nullable();
            $table->decimal('tx_power', 8, 2)->nullable();
            $table->decimal('temp', 8, 2)->nullable();
            $table->string('status')->default('online'); // online, offline, dying-gasp
            $table->timestamp('last_polled_at');
            $table->timestamps();

            $table->unique(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_signal_caches');
        Schema::dropIfExists('olt_status_logs');
    }
};
