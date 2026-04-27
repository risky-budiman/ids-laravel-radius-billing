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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'olt_id')) {
                $table->foreignId('olt_id')->nullable()->constrained('olts')->onDelete('set null');
            }
            if (!Schema::hasColumn('customers', 'onu_sn')) {
                $table->string('onu_sn')->nullable()->index();
            }
            if (!Schema::hasColumn('customers', 'onu_index')) {
                $table->string('onu_index')->nullable(); // .shelf.slot.port.onu_id
            }
            if (!Schema::hasColumn('customers', 'onu_type')) {
                $table->string('onu_type')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('olt_id');
            $table->dropColumn(['onu_sn', 'onu_index', 'onu_type']);
        });
    }
};
