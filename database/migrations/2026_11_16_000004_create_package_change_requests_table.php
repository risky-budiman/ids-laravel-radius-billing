<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pengajuan ganti paket (diajukan CS, disetujui admin/administrator). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('from_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('to_package_id')->constrained('packages');
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending')->index();   // pending, approved, rejected, canceled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_change_requests');
    }
};
