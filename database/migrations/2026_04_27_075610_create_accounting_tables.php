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
        // 1. Chart of Accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 1101
            $table->string('name'); // e.g., Kas Tunai
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Journals (Header)
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference')->nullable(); // Invoice ID, Trans ID, etc.
            $table->string('description');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 3. Journal Items (Lines)
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 4. Link Bank Accounts to CoA
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('chart_of_account_id')->nullable()->after('id')->constrained('chart_of_accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chart_of_account_id');
        });
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('chart_of_accounts');
    }
};
