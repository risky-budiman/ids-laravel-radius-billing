<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->integer('fup_limit')->nullable()->comment('Limit in GB');
            $table->string('fup_speed_down', 20)->nullable()->comment('Speed after FUP');
        });

        Schema::create('boosters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('quota_gb');
            $table->decimal('price', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_boosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('booster_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_paid', 15, 2);
            $table->string('payment_status')->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_boosters');
        Schema::dropIfExists('boosters');
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['fup_limit', 'fup_speed_down']);
        });
    }
};
