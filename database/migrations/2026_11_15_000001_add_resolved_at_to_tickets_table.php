<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Waktu tiket diselesaikan (resolved/closed), dipakai rekap pekerjaan tim
 * (durasi penyelesaian & SLA). Tiket lama diisi dari riwayat status sistem
 * ("... menjadi 'CLOSED'"), atau updated_at bila riwayat tidak ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'resolved_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->timestamp('resolved_at')->nullable()->after('status');
                $table->index('resolved_at');
            });
        }

        DB::table('tickets')
            ->whereIn('status', ['resolved', 'closed'])
            ->whereNull('resolved_at')
            ->orderBy('id')
            ->chunkById(500, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $fromHistory = DB::table('ticket_replies')
                        ->where('ticket_id', $ticket->id)
                        ->where('is_system', true)
                        ->where(function ($q) {
                            $q->where('message', 'like', "%menjadi 'RESOLVED'%")
                              ->orWhere('message', 'like', "%menjadi 'CLOSED'%");
                        })
                        // RESOLVED -> CLOSED hanya administrasi; pekerjaan selesai saat pertama masuk status selesai.
                        ->where('message', 'not like', "%dari 'RESOLVED'%")
                        ->where('message', 'not like', "%dari 'CLOSED'%")
                        ->max('created_at');   // penyelesaian terakhir (bila pernah dibuka ulang)

                    DB::table('tickets')->where('id', $ticket->id)->update([
                        'resolved_at' => $fromHistory ?? $ticket->updated_at ?? $ticket->created_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'resolved_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex(['resolved_at']);
                $table->dropColumn('resolved_at');
            });
        }
    }
};
