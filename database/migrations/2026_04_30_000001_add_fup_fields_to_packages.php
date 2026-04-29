<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('enable_fup')->default(false)->after('price');
            $table->integer('fup_limit_gb')->nullable()->after('enable_fup'); // Batas kuota dalam GB
            $table->string('fup_speed_limit')->nullable()->after('fup_limit_gb'); // Kecepatan setelah FUP (misal: 2M/2M)
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('current_month_usage_gb', 15, 2)->default(0)->after('is_active');
            $table->timestamp('last_usage_sync')->nullable()->after('current_month_usage_gb');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['enable_fup', 'fup_limit_gb', 'fup_speed_limit']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['current_month_usage_gb', 'last_usage_sync']);
        });
    }
};
