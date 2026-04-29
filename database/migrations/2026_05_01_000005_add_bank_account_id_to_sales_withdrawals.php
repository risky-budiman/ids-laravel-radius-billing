<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_withdrawals', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('amount')->constrained('bank_accounts');
        });
    }

    public function down()
    {
        Schema::table('sales_withdrawals', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
    }
};
