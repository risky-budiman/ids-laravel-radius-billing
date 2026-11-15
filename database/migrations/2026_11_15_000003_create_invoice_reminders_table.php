<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan pengingat tagihan WhatsApp yang sudah dikirim, agar satu invoice tidak
 * diingatkan dua kali untuk hari yang sama (mis. H-3) walau command dijalankan ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('days_before_due');   // 3 = H-3, 0 = hari H, -1 = H+1
            $table->timestamp('sent_at');
            $table->unique(['invoice_id', 'days_before_due']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
    }
};
