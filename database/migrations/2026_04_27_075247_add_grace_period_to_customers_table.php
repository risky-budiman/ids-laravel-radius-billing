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
            if (!Schema::hasColumn('customers', 'activation_grace_expires_at')) {
                $table->timestamp('activation_grace_expires_at')->nullable();
            }
            if (!Schema::hasColumn('customers', 'installation_paid_at')) {
                $table->timestamp('installation_paid_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('customers', 'activation_grace_expires_at')) $columns[] = 'activation_grace_expires_at';
            if (Schema::hasColumn('customers', 'installation_paid_at')) $columns[] = 'installation_paid_at';
            
            if (count($columns) > 0) {
                $table->dropColumn($columns);
            }
        });
    }
};
