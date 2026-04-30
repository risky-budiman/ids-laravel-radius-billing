<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->integer('snmp_version')->default(2)->after('snmp_port');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn('snmp_version');
        });
    }
};
