<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gangguan massal per OLT / port PON / ODP: satu tiket induk, daftar pelanggan terdampak, tiket gangguan
 * pelanggan yang tertaut (tickets.incident_id), dan WhatsApp pemberitahuan / pemulihan (opsional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 10);            // olt | port | odp
            $table->string('scope_id', 64);
            $table->string('scope_label', 191);
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('estimate', 100)->nullable();  // estimasi perbaikan (teks / tanggal)
            $table->string('status', 10)->default('open')->index();   // open | resolved
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->unsignedInteger('notified_count')->default(0);
            $table->unsignedInteger('resolved_notified_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('network_incident_customer', function (Blueprint $table) {
            $table->foreignId('network_incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->primary(['network_incident_id', 'customer_id']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('incident_id')->nullable()->after('customer_id')->constrained('network_incidents')->nullOnDelete();
        });

        // Template WA pemulihan (template "Gangguan Massal" sudah ada sejak awal)
        DB::table('whatsapp_templates')->insertOrIgnore([
            'name' => 'Gangguan Teratasi',
            'type' => 'gangguan_pulih',
            'message' => "Halo *{name}*,\n\nGangguan jaringan di wilayah Anda sudah *teratasi* dan layanan internet kembali normal sejak *{tanggal}*.\n\nBila internet Anda masih bermasalah, silakan matikan lalu nyalakan kembali modem, atau hubungi kami.\n\nTerima kasih atas kesabaran Anda.",
            'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{tanggal}']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('incident_id');
        });
        Schema::dropIfExists('network_incident_customer');
        Schema::dropIfExists('network_incidents');
        DB::table('whatsapp_templates')->where('type', 'gangguan_pulih')->delete();
    }
};
