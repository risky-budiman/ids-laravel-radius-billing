<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Akun kontra aset untuk penyusutan (Cr saat penyusutan bulanan). COA bawaan hanya punya 5201 Beban Penyusutan,
 * sehingga aset tetap terpaksa memakai akun lain sebagai "akumulasi". Saldo kreditnya mengurangi total aset.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('chart_of_accounts')->where('code', '1290')->exists()) {
            return;
        }
        DB::table('chart_of_accounts')->insert(['code' => '1290', 'name' => 'Akumulasi Penyusutan Aset Tetap', 'type' => 'asset',
            'parent_id' => DB::table('chart_of_accounts')->where('code', '1000')->value('id'), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Akun dibiarkan bila sudah dipakai jurnal
        if (! DB::table('journal_items')->whereIn('account_id', DB::table('chart_of_accounts')->where('code', '1290')->pluck('id'))->exists()) {
            DB::table('chart_of_accounts')->where('code', '1290')->delete();
        }
    }
};
