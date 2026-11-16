<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredensial SNMPv3 (USM) per OLT. Password auth/priv disimpan terenkripsi (cast 'encrypted' di model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('snmp_v3_user', 64)->nullable()->after('snmp_write_community');
            $table->string('snmp_v3_level', 20)->nullable()->after('snmp_v3_user');
            $table->string('snmp_v3_auth_protocol', 10)->nullable()->after('snmp_v3_level');
            $table->text('snmp_v3_auth_password')->nullable()->after('snmp_v3_auth_protocol');
            $table->string('snmp_v3_priv_protocol', 10)->nullable()->after('snmp_v3_auth_password');
            $table->text('snmp_v3_priv_password')->nullable()->after('snmp_v3_priv_protocol');
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn(['snmp_v3_user', 'snmp_v3_level', 'snmp_v3_auth_protocol', 'snmp_v3_auth_password',
                'snmp_v3_priv_protocol', 'snmp_v3_priv_password']);
        });
    }
};
