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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->after('quantity')->default(0);
            $table->decimal('subtotal', 15, 2)->after('unit_price')->default(0);
            $table->foreignId('tax_id')->nullable()->after('subtotal')->constrained('taxes')->onDelete('set null');
            $table->decimal('tax_amount', 15, 2)->after('tax_id')->default(0);
            $table->decimal('total_amount', 15, 2)->after('tax_amount')->default(0);
            $table->foreignId('supplier_id')->nullable()->after('total_amount')->constrained('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['unit_price', 'subtotal', 'tax_id', 'tax_amount', 'total_amount', 'supplier_id']);
        });
    }
};
