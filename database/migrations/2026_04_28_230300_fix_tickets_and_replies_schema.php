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
        // Add attachment column to tickets table
        if (Schema::hasTable('tickets') && !Schema::hasColumn('tickets', 'attachment')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('attachment')->nullable()->after('description');
            });
        }

        // Make user_id nullable in ticket_replies table
        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'attachment')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }

        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable(false)->change();
            });
        }
    }
};
