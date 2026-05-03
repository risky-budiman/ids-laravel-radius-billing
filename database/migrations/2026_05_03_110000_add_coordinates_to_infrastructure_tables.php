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
        Schema::table('regions', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('name');
            $table->string('longitude')->nullable()->after('latitude');
        });

        Schema::table('stos', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('name');
            $table->string('longitude')->nullable()->after('latitude');
        });

        Schema::table('stbs', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('name');
            $table->string('longitude')->nullable()->after('latitude');
        });

        Schema::table('olts', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('description');
            $table->string('longitude')->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('stos', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('stbs', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
