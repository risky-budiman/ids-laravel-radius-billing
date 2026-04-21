<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nas')) {
            Schema::create('nas', function (Blueprint $table) {
                $table->id();
                $table->string('nasname', 128);
                $table->string('shortname', 32)->nullable();
                $table->string('type', 30)->default('other');
                $table->integer('ports')->nullable();
                $table->string('secret', 60)->default('secret');
                $table->string('server', 64)->nullable();
                $table->string('community', 50)->nullable();
                $table->string('description', 200)->default('RADIUS Client');
                
                $table->index('nasname');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nas');
    }
};
