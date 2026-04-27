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
        Schema::table('odcs', function (Blueprint $table) {
            $table->unsignedBigInteger('stb_id')->nullable()->after('name');
            $table->foreign('stb_id')->references('id')->on('stbs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['stb_id']);
            $table->dropColumn('stb_id');
        });
    }
};
