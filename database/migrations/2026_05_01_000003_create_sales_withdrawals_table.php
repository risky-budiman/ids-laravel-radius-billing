<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_withdrawals', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sales_id')->constrained('users')->onDelete('cascade');
            $blueprint->decimal('amount', 15, 2);
            $blueprint->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $blueprint->string('payment_method')->nullable(); // Cash, Bank Transfer, dsb
            $blueprint->string('reference_number')->nullable(); // No Transaksi Bank
            $blueprint->text('admin_notes')->nullable();
            $blueprint->timestamp('processed_at')->nullable();
            $blueprint->foreignId('processed_by')->nullable()->constrained('users');
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_withdrawals');
    }
};
