<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_commissions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sales_id')->constrained('users')->onDelete('cascade');
            $blueprint->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $blueprint->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $blueprint->decimal('base_amount', 15, 2); // Nilai invoice
            $blueprint->decimal('commission_rate', 15, 2);
            $blueprint->enum('commission_type', ['percentage', 'fixed']);
            $blueprint->decimal('commission_amount', 15, 2); // Hasil perhitungan
            $blueprint->enum('status', ['pending', 'paid'])->default('pending');
            $blueprint->foreignId('withdrawal_id')->nullable(); // Terisi jika sudah ditarik
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_commissions');
    }
};
