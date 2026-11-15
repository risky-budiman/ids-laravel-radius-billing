<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sejak kapan pelanggan terisolir — dasar pengajuan dismantle otomatis.
 * Data lama: perubahan status terakhir ke "suspended" di Activity Log, atau updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'suspended_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->timestamp('suspended_at')->nullable()->after('terminated_at');
                $table->index('suspended_at');
            });
        }

        $suspended = DB::table('customers')->where('status', 'suspended')->whereNull('suspended_at')->get(['id', 'updated_at']);
        foreach ($suspended as $c) {
            $when = null;
            if (Schema::hasTable('activity_logs')) {
                foreach (DB::table('activity_logs')->where('subject_type', 'App\\Models\\Customer')->where('subject_id', $c->id)
                    ->where('properties', 'like', '%suspended%')->orderByDesc('created_at')->get(['properties', 'created_at']) as $log) {
                    if ((json_decode((string) $log->properties, true)['new']['status'] ?? null) === 'suspended') {
                        $when = $log->created_at;
                        break;
                    }
                }
            }
            DB::table('customers')->where('id', $c->id)->update(['suspended_at' => $when ?? $c->updated_at]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'suspended_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['suspended_at']);
                $table->dropColumn('suspended_at');
            });
        }
    }
};
