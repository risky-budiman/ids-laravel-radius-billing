<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda peringatan tiket yang sudah dikirim ke grup WhatsApp teknisi (sekali per tiket).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('unclaimed_alerted_at')->nullable()->after('resolved_at');
            $table->timestamp('sla_alerted_at')->nullable()->after('unclaimed_alerted_at');
        });

        // Tiket terbuka yang sudah ada dianggap sudah diperingatkan: tanpa ini, run pertama
        // mengirim satu pesan per tiket lama sekaligus ke grup teknisi. Tumpukan lama tetap
        // terlihat di Rekap Tiket; peringatan otomatis berlaku untuk tiket baru.
        \Illuminate\Support\Facades\DB::table('tickets')
            ->whereIn('status', ['open', 'in_progress'])
            ->update(['unclaimed_alerted_at' => now(), 'sla_alerted_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['unclaimed_alerted_at', 'sla_alerted_at']);
        });
    }
};
