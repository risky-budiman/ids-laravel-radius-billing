<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $blueprint) {
            $blueprint->foreignId('sales_id')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->decimal('sales_commission_rate', 15, 2)->nullable();
            $blueprint->enum('sales_commission_type', ['percentage', 'fixed'])->nullable();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['sales_id']);
            $blueprint->dropColumn(['sales_id', 'sales_commission_rate', 'sales_commission_type']);
        });
    }
};
