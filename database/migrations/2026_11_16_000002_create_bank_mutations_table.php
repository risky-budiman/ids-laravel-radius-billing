<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mutasi bank dari webhook (Moota). Setiap mutasi dicatat sekali (fingerprint unik) sehingga
 * kiriman ulang tidak memproses ulang, dan mutasi yang tidak cocok bisa dicocokkan manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('moota');
            $table->string('fingerprint', 100)->unique();
            $table->string('mutation_id', 100)->nullable();
            $table->string('bank_id', 100)->nullable();
            $table->string('type', 5);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamp('mutation_at')->nullable();
            $table->string('status', 20)->default('unmatched')->index();   // matched, manual, unmatched, ignored
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_mutations');
    }
};
