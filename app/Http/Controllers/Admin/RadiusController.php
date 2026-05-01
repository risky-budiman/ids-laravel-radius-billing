<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Radius\RadAcct;
use App\Services\RadiusCoAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusController extends Controller
{
    protected $coaService;

    public function __construct(RadiusCoAService $coaService)
    {
        $this->coaService = $coaService;
    }

    /**
     * Disconnect all online RADIUS sessions.
     */
    public function disconnectAll(Request $request)
    {
        // Prevent timeout for large numbers of users
        set_time_limit(0);
        
        $onlineSessions = RadAcct::online()->get();
        $count = 0;
        $failed = 0;
        
        // Cache NAS info to avoid redundant DB queries
        $nasCache = [];

        foreach ($onlineSessions as $session) {
            $nasIp = $session->nasipaddress;
            
            if (!isset($nasCache[$nasIp])) {
                $nasCache[$nasIp] = DB::connection('radius')->table('nas')->where('nasname', $nasIp)->first();
            }
            
            $nas = $nasCache[$nasIp];
            
            if ($nas) {
                $success = $this->coaService->disconnect($nas->nasname, $nas->secret, $session->username);
                if ($success) {
                    $count++;
                } else {
                    // If disconnect fails (e.g., NAS unreachable), PERMANENTLY DELETE the stale online session
                    $session->delete();
                    $failed++;
                }
            } else {
                Log::warning("NAS Info not found for IP: {$nasIp}. Deleting stale session.");
                $session->delete();
                $failed++;
            }
        }

        return back()->with('success', "Proses selesai. Berhasil memutus {$count} sesi. " . ($failed > 0 ? "Berhasil menghapus {$failed} sesi online yang macet/tidak merespon." : ""));
    }

    /**
     * Clear stale sessions (sessions without stop time that are actually dead).
     */
    public function clearStaleSessions()
    {
        // This is a common maintenance task
        // We mark sessions as closed if they haven't been updated in a while (e.g. 2 hours)
        // assuming Acct-Interim-Interval is working.
        
        $affected = RadAcct::online()
            ->where('acctupdatetime', '<', now()->subHours(2))
            ->update([
                'acctstoptime' => now(),
                'acctterminatecause' => 'Stale-Session-Cleared'
            ]);

        return back()->with('success', "Berhasil membersihkan {$affected} sesi menggantung.");
    }
}
