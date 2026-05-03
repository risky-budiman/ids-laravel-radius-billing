<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('scheduled_activation_at')->nullable()->after('activated_at');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('scheduled_activation_at');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
