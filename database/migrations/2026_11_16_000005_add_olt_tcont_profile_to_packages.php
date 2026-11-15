<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Nama profil T-CONT di OLT per paket (opsional). Dipakai saat provisioning & ganti paket. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('olt_tcont_profile', 50)->nullable()->after('upload_speed');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('olt_tcont_profile');
        });
    }
};
