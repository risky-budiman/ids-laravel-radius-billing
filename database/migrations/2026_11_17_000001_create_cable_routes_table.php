<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jalur kabel di peta: satu baris per sambungan "titik → induknya" (pelanggan→ODP, ODP→ODC, ODC→STB, STB→STO, STO→Wilayah).
 * path = daftar [lat, lng] dari induk ke titik, mengikuti jalan/tiang. Tanpa baris = garis lurus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cable_routes', function (Blueprint $table) {
            $table->id();
            $table->string('node_type', 16);   // customer | odp | odc | stb | sto
            $table->string('node_id', 64);
            $table->json('path');
            $table->unsignedInteger('length_m')->default(0);
            $table->string('mode', 10)->default('manual');   // manual | road
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['node_type', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cable_routes');
    }
};
