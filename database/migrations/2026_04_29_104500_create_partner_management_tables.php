<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Users table for Partner support
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('commission_rate', 10, 2)->nullable()->after('role');
            $table->string('commission_type')->default('percentage')->after('commission_rate'); // percentage, fixed
            $table->string('bank_name')->nullable()->after('commission_type');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
        });

        // 2. Update Customers table to link to Partner
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            $table->decimal('commission_rate', 10, 2)->nullable()->after('partner_id');
            $table->string('commission_type')->nullable()->after('commission_rate');
        });

        // 3. Create Partner Commissions table
        Schema::create('partner_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('base_amount', 15, 2); // The payment amount commission was calculated from
            $table->decimal('rate', 10, 2); // Rate at the time of calculation
            $table->string('type'); // percentage, fixed
            $table->string('status')->default('earned'); // earned, withdrawn
            $table->foreignId('withdrawal_id')->nullable(); // linked when withdrawn
            $table->timestamps();
        });

        // 4. Create Partner Withdrawals table
        Schema::create('partner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('request_date');
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending'); // pending, approved, paid, rejected
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->foreignId('journal_id')->nullable(); // link to accounting journal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_withdrawals');
        Schema::dropIfExists('partner_commissions');
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn(['partner_id', 'commission_rate', 'commission_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_type', 'bank_name', 'bank_account_number', 'bank_account_name']);
        });
    }
};
