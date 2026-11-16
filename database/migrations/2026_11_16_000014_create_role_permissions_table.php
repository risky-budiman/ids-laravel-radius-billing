<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengecualian hak akses per role yang diatur administrator di halaman "Hak Akses per Role".
 * Tanpa baris = aturan bawaan (middleware role pada rute). allowed true = diizinkan, false = ditolak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('route', 150);
            $table->boolean('allowed');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['role', 'route']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
