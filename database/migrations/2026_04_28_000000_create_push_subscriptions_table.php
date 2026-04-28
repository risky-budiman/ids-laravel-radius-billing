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
        Schema::create('push_subscriptions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->morphs('subscribable'); // Can be Customer or User
            $blueprint->string('endpoint', 500)->unique();
            $blueprint->string('public_key')->nullable();
            $blueprint->string('auth_token')->nullable();
            $blueprint->string('content_encoding')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
