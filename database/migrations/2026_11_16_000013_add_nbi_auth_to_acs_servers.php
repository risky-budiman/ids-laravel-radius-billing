<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login NBI GenieACS (Basic Auth, biasanya lewat reverse proxy nginx di depan port 7557). Tanpa ini NBI harus
 * terbuka tanpa login sehingga siapa pun yang menjangkau port itu bisa mengubah modem pelanggan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acs_servers', function (Blueprint $table) {
            $table->string('nbi_username')->nullable()->after('url');
            $table->text('nbi_password')->nullable()->after('nbi_username');   // terenkripsi (cast encrypted)
        });
    }

    public function down(): void
    {
        Schema::table('acs_servers', function (Blueprint $table) {
            $table->dropColumn(['nbi_username', 'nbi_password']);
        });
    }
};
