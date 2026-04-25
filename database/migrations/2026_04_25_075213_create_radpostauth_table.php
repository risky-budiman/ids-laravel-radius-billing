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
        Schema::create('radpostauth', function (Blueprint $table) {
            $table->integer('id', true); // INT(11) AUTO_INCREMENT
            $table->string('username', 64);
            $table->string('pass', 64)->nullable();
            $table->string('reply', 32)->nullable();
            $table->timestamp('authdate')->useCurrent();

            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radpostauth');
    }
};
