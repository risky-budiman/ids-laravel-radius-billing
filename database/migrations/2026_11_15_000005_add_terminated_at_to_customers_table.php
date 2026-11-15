<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal layanan pelanggan berakhir (dicabut / batal), untuk menghitung churn per bulan.
 * Data lama diisi dari Activity Log (perubahan status terakhir ke dismantled/canceled),
 * atau updated_at bila riwayat tidak ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'terminated_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->timestamp('terminated_at')->nullable()->after('activated_at');
                $table->index('terminated_at');
            });
        }

        $ended = DB::table('customers')->whereIn('status', ['dismantled', 'canceled'])->whereNull('terminated_at')
            ->get(['id', 'status', 'updated_at']);

        foreach ($ended as $c) {
            $when = null;
            if (Schema::hasTable('activity_logs')) {
                $logs = DB::table('activity_logs')
                    ->where('subject_type', 'App\\Models\\Customer')->where('subject_id', $c->id)
                    ->where('properties', 'like', '%' . $c->status . '%')
                    ->orderByDesc('created_at')->get(['properties', 'created_at']);
                foreach ($logs as $log) {
                    $new = json_decode((string) $log->properties, true)['new']['status'] ?? null;
                    if ($new === $c->status) {
                        $when = $log->created_at;
                        break;
                    }
                }
            }
            DB::table('customers')->where('id', $c->id)->update(['terminated_at' => $when ?? $c->updated_at]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'terminated_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['terminated_at']);
                $table->dropColumn('terminated_at');
            });
        }
    }
};
