<?php

namespace App\Jobs;

use App\Models\Radius\RadAcct;
use App\Services\RadiusCoAService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisconnectAllSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RadiusCoAService $coaService): void
    {
        Log::info("Starting background job: DisconnectAllSessionsJob");

        $onlineSessions = RadAcct::online()->get();
        $count = 0;
        $failed = 0;
        $nasCache = [];

        foreach ($onlineSessions as $session) {
            $nasIp = $session->nasipaddress;
            
            if (!isset($nasCache[$nasIp])) {
                $nasCache[$nasIp] = DB::connection('radius')->table('nas')->where('nasname', $nasIp)->first();
            }
            
            $nas = $nasCache[$nasIp];
            
            if ($nas) {
                $success = $coaService->disconnect($nas->nasname, $nas->secret, $session->username);
                if ($success) {
                    $count++;
                } else {
                    $session->delete();
                    $failed++;
                }
            } else {
                $session->delete();
                $failed++;
            }
        }

        Log::info("Background job finished: Disconnected {$count} sessions, Deleted {$failed} stale sessions.");
    }
}
