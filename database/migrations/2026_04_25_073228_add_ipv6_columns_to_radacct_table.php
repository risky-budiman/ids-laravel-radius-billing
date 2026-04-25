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
        Schema::table('radacct', function (Blueprint $table) {
            $table->string('framedipv6address', 45)->nullable()->after('framedipaddress');
            $table->string('framedipv6prefix', 45)->nullable()->after('framedipv6address');
            $table->string('delegatedipv6prefix', 45)->nullable()->after('framedipv6prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            $table->dropColumn(['framedipv6address', 'framedipv6prefix', 'delegatedipv6prefix']);
        });
    }
};
