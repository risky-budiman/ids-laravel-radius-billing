<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_code')->unique();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_months');
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->string('status')->default('active'); // active, disposed
            $table->unsignedBigInteger('asset_account_id');
            $table->unsignedBigInteger('depreciation_account_id');
            $table->unsignedBigInteger('accumulated_account_id');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('asset_account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('depreciation_account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('accumulated_account_id')->references('id')->on('chart_of_accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
