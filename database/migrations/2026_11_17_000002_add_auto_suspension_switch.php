<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sakelar isolir otomatis (Pengaturan Isolir). Versi lama tidak pernah mengisolir penunggak karena bug, jadi
 * update di server yang sudah berisi pelanggan akan mengisolir semua penunggak sekaligus pada proses billing
 * pertama. Karena itu di server lama sakelar dimulai NONAKTIF — administrator memeriksa daftar penunggak
 * lalu mengaktifkannya sendiri. Instalasi baru (tanpa pelanggan) tetap aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'isolir_auto_enabled')->exists();
        if (! $exists && DB::table('customers')->exists()) {
            DB::table('settings')->insert(['key' => 'isolir_auto_enabled', 'value' => '0', 'type' => 'boolean',
                'created_at' => now(), 'updated_at' => now()]);
            Cache::forget('app_settings');
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'isolir_auto_enabled')->delete();
        Cache::forget('app_settings');
    }
};
