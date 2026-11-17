<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mutasi rekening dari bank (CSV/Excel internet banking) untuk rekonsiliasi dengan catatan Kas & Bank aplikasi.
 * Satu baris mutasi dicocokkan dengan paling banyak satu transaksi aplikasi (bank_transaction_id unik).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('file_name', 191);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_new')->default(0);
            $table->decimal('opening_balance', 15, 2)->nullable();   // saldo awal menurut bank (bila ada kolom saldo)
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('line_date')->index();
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 15, 2);                 // + masuk (kredit), - keluar (debit)
            $table->decimal('balance', 15, 2)->nullable();    // saldo setelah transaksi menurut bank
            $table->string('fingerprint', 64)->unique();
            $table->string('status', 10)->default('unmatched')->index();   // unmatched | matched | ignored
            $table->string('match_type', 10)->nullable();     // auto | manual
            $table->foreignId('bank_transaction_id')->nullable()->unique()->constrained('bank_transactions')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['bank_account_id', 'line_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
    }
};
