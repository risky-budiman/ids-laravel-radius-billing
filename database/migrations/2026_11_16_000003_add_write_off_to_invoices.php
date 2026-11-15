<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Penghapusan piutang macet (write-off): status invoice baru + siapa, kapan, alasan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'written_off'])->default('unpaid')->change();
            $table->timestamp('written_off_at')->nullable()->after('paid_at');
            $table->foreignId('written_off_by')->nullable()->after('written_off_at')->constrained('users')->nullOnDelete();
            $table->string('write_off_reason', 500)->nullable()->after('written_off_by');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('written_off_by');
            $table->dropColumn(['written_off_at', 'write_off_reason']);
        });
        \Illuminate\Support\Facades\DB::table('invoices')->where('status', 'written_off')->update(['status' => 'unpaid']);
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid')->change();
        });
    }
};
