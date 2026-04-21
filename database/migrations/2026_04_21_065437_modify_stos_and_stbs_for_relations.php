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
            $table->foreignId('region_id')->nullable()->after('id')->constrained('regions')->cascadeOnDelete();
        });
        Schema::table('stbs', function (Blueprint $table) {
            $table->foreignId('sto_id')->nullable()->after('id')->constrained('stos')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stbs', function (Blueprint $table) {
            $table->dropForeign(['sto_id']);
            $table->dropColumn('sto_id');
        });
        Schema::table('stos', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
