<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akses OLT hanya lewat SNMP: kolom telnet (port, username, password, enable password) dan pilihan metode
 * akses dihapus — termasuk password telnet yang tersimpan.
 */
return new class extends Migration
{
    private const COLUMNS = ['access_method', 'telnet_port', 'username', 'password', 'enable_password'];

    public function up(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn ($c) => Schema::hasColumn('olts', $c)));
        if ($existing) {
            Schema::table('olts', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->string('access_method', 20)->default('snmp_telnet');
            $table->integer('telnet_port')->default(23);
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('enable_password')->nullable();
        });
    }
};
