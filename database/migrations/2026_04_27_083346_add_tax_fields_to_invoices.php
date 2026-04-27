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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->after('amount')->default(0);
            $table->foreignId('tax_id')->nullable()->after('subtotal')->constrained('taxes')->onDelete('set null');
            $table->decimal('tax_amount', 15, 2)->after('tax_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropColumn(['subtotal', 'tax_id', 'tax_amount']);
        });
    }
};
