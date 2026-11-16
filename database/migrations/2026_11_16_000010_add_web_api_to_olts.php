<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login API web OLT (HSGQ) untuk registrasi/allow ONU dari aplikasi. Password disimpan terenkripsi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('web_username', 64)->nullable()->after('snmp_v3_priv_password');
            $table->text('web_password')->nullable()->after('web_username');
            $table->unsignedInteger('web_port')->default(80)->after('web_password');
            $table->boolean('web_https')->default(false)->after('web_port');
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn(['web_username', 'web_password', 'web_port', 'web_https']);
        });
    }
};
