<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pekerjaan yang harus dilakukan teknisi langsung di OLT (sistem hanya SNMP): daftarkan/allow ONU,
 * hapus ONU, ganti profil T-CONT. Dulu hanya tercatat di Log Aktivitas sehingga mudah terlewat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('type', 20);                 // provision | delete | profile
            $table->string('onu_sn', 40)->nullable();
            $table->string('onu_index', 40)->nullable();
            $table->unsignedSmallInteger('vlan')->nullable();
            $table->string('profile', 60)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending');   // pending | done | dismissed
            $table->string('resolution', 20)->nullable();       // manual | auto (dicek lewat SNMP)
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['olt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_work_items');
    }
};
