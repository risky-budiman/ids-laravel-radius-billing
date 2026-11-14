<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan isolir aktif per pelanggan: IP isolir yang dialokasikan (mode rentang IP)
 * dan atribut RADIUS asli yang dipindahkan sementara agar bisa dipulihkan saat lunas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_isolations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username')->index();
            $table->string('mode', 10);                       // range | pool
            $table->string('ip_address', 45)->nullable()->unique();
            $table->json('previous_groups')->nullable();       // radusergroup asli
            $table->json('previous_replies')->nullable();      // radreply asli (Framed-IP-Address / Framed-Pool)
            $table->timestamp('isolated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_isolations');
    }
};
