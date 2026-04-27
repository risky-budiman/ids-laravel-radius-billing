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
            $table->decimal('installation_fee', 15, 2)->default(0)->after('package_id');
            $table->datetime('installation_paid_at')->nullable()->after('installation_fee');
            $table->foreignId('installation_bank_account_id')->nullable()->constrained('bank_accounts')->after('installation_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['installation_bank_account_id']);
            $table->dropColumn(['installation_fee', 'installation_paid_at', 'installation_bank_account_id']);
        });
    }
};
