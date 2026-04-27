<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odps', function (Blueprint $table) {
            $table->string('id')->primary(); // E.g., ODP-BGR-001
            $table->string('name');
            $table->string('odc_id')->nullable();
            $table->foreign('odc_id')->references('id')->on('odcs')->onDelete('set null');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->integer('total_ports')->default(8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
