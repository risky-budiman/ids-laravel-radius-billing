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
        Schema::table('stos', function (Blueprint $table) {
            $table->foreignId('acs_server_id')->nullable()->constrained('acs_servers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acs_server_id');
        });
    }
};
