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
        // Dispatch the job to the background queue
        \App\Jobs\DisconnectAllSessionsJob::dispatch();

        return back()->with('success', "Proses pemutusan koneksi telah dimulai di latar belakang. Daftar online akan segera diperbarui secara bertahap.");
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
