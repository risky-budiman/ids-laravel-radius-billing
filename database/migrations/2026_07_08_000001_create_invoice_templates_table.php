<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format')->default('A4'); // A4 or Thermal
            $table->boolean('is_default')->default(false);
            $table->longText('html_content'); // The HTML template body
            $table->text('css_content')->nullable(); // Optional custom CSS
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
