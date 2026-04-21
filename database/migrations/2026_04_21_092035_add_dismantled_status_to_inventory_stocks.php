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
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->enum('status', ['ready', 'installed', 'damaged', 'lost', 'returned', 'dismantled'])->default('ready')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            //
        });
    }
};
