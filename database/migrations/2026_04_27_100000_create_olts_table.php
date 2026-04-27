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
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_read_community')->default('public');
            $table->string('snmp_write_community')->default('private');
            $table->integer('telnet_port')->default(23);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('olt_type')->default('ZTE_C320'); // ZTE_C300, ZTE_C320, etc.
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('olt_pon_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->integer('slot'); // Slot number (e.g., 1)
            $table->integer('pon_port'); // Port number (e.g., 1-16)
            $table->string('status')->default('active'); // active, maintenance, full
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate slot/port on the same OLT
            $table->unique(['olt_id', 'slot', 'pon_port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olt_pon_ports');
        Schema::dropIfExists('olts');
    }
};
