<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metode akses per OLT: 'snmp_telnet' (bawaan, perilaku lama) atau 'snmp' (SNMP saja: tidak ada perintah telnet;
 * registrasi/hapus/profil ONU dikerjakan manual di OLT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('access_method', 20)->default('snmp_telnet')->after('olt_type');
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn('access_method');
        });
    }
};
