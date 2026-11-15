<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita Acara (BA) Instalasi: salinan data pemasangan saat aktivasi + tanda tangan digital
 * pelanggan & teknisi. Tautan tanda tangan pelanggan memakai token acak (dikirim via WhatsApp).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                     // BA/2026/11/0001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot');                                // data pelanggan, perangkat, material, lokasi
            $table->text('terms')->nullable();                       // klausul saat BA dibuat
            $table->string('sign_token', 64)->unique();
            $table->string('customer_signature')->nullable();
            $table->string('customer_signer_name')->nullable();
            $table->timestamp('customer_signed_at')->nullable();
            $table->string('technician_signature')->nullable();
            $table->timestamp('technician_signed_at')->nullable();
            $table->json('sign_meta')->nullable();                   // lokasi & IP saat tanda tangan
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'installation_reminder_sent_at')) {
                $table->timestamp('installation_reminder_sent_at')->nullable()->after('activation_grace_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_reports');
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'installation_reminder_sent_at')) {
                $table->dropColumn('installation_reminder_sent_at');
            }
        });
    }
};
