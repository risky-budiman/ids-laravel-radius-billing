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
            $table->enum('billing_type', ['prepaid', 'postpaid'])->default('postpaid')->after('package_id');
            $table->enum('billing_method', ['cycle', 'fixed', 'renewal'])->default('cycle')->after('billing_type');
            $table->integer('billing_day')->default(1)->after('billing_method');
            $table->integer('billing_due_day')->default(20)->after('billing_day');
            $table->date('billing_next_date')->nullable()->after('billing_due_day');
            $table->date('billing_due_date')->nullable()->after('billing_next_date');
            $table->date('expired_at')->nullable()->after('billing_due_date');
            $table->timestamp('activated_at')->nullable()->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'billing_type',
                'billing_method',
                'billing_day',
                'billing_due_day',
                'billing_next_date',
                'billing_due_date',
                'expired_at',
                'activated_at'
            ]);
        });
    }
};
