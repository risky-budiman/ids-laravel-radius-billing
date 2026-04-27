<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odcs', function (Blueprint $table) {
            $table->string('id')->primary(); // E.g., ODC-BGR-001
            $table->string('name');
            $table->text('location_description')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->integer('total_ports')->default(144);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odcs');
    }
};
