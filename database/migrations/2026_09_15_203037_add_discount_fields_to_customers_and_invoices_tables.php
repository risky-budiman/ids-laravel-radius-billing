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
            if (!Schema::hasColumn('customers', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('package_id'); // 'fixed' (Rp) or 'percentage' (%)
            }
            if (!Schema::hasColumn('customers', 'discount_value')) {
                $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('subtotal');
            }
            if (!Schema::hasColumn('invoices', 'discount_value')) {
                $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
