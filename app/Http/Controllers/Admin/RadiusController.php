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
        $onlineSessions = RadAcct::online()->get();
        $count = 0;
        $failed = 0;

        foreach ($onlineSessions as $session) {
            // We need NAS IP and Secret to send PoD
            // Usually NAS Info is in nas table
            $nas = DB::connection('radius')->table('nas')->where('nasname', $session->nasipaddress)->first();
            
            if ($nas) {
                $success = $this->coaService->disconnect($nas->nasname, $nas->secret, $session->username);
                if ($success) {
                    $count++;
                } else {
                    $failed++;
                }
            } else {
                Log::warning("NAS Info not found for IP: {$session->nasipaddress}");
                $failed++;
            }
        }

        // Also optionally clear stale sessions from DB if they don't have a stop time but NAS is rebooted
        // But for "Disconnect All", we just try to send PoD to everyone.

        return back()->with('success', "Berhasil mengirim perintah disconnect ke {$count} sesi. " . ($failed > 0 ? "Gagal pada {$failed} sesi." : ""));
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
