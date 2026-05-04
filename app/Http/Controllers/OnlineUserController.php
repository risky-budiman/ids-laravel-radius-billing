<?php

namespace App\Http\Controllers;

use App\Models\Radius\RadAcct;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use Illuminate\Http\Request;

class OnlineUserController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'online'); // default to online
        
        $onlineUsernames = RadAcct::online()->pluck('username')->toArray();

        if ($status === 'offline') {
            $offlineUsers = \App\Models\Customer::with('package')
                ->whereNotIn('username', $onlineUsernames)
                ->paginate(20);
                
            return view('online-users.index', compact('offlineUsers', 'status'));
        }

        // Default: Online Users (Truly Online)
        $onlineUsers = RadAcct::online()
            ->orderBy('acctstarttime', 'desc')
            ->paginate(20);
            
        return view('online-users.index', compact('onlineUsers', 'status'));
    }

    public function kick(Request $request, $radacctid)
    {
        $session = RadAcct::findOrFail($radacctid);
        $isForce = $request->has('force');
        
        // Find NAS for this session
        $nas = Nas::where('shortname', $session->nasipaddress)
                  ->orWhere('nasname', $session->nasipaddress)
                  ->first();
        
        if (!$nas) {
            // If NAS not in DB, we allow manual force close if requested
            if ($isForce) {
                $this->performForceClose($session);
                return back()->with('success', "NAS not found. Session for {$session->username} has been manually force closed.");
            }
            return back()->with('error', "NAS not found. Cannot send CoA Disconnect. Use 'Force Close' if you are sure the session is stale.");
        }

        // If it's a manual force close request, skip CoA and just update DB
        if ($isForce) {
            $this->performForceClose($session);
            return back()->with('success', "Session for {$session->username} has been manually force closed in database.");
        }

        $coa = new RadiusCoAService();
        $success = $coa->disconnect($nas->nasname, $nas->secret, $session->username, $session->acctsessionid);

        if ($success) {
            return back()->with('success', "Disconnect signal (CoA) sent successfully to Router for {$session->username}.");
        } else {
            // NEW RULE: If CoA fails, DO NOT automatically force close in DB if user is still online.
            // This prevents "rancu" (data inconsistency) and ensures the user cannot re-session 
            // until the modem is restarted (stale session remains in DB blocking new ones).
            return back()->with('error', "NAS Unreachable/CoA Failed. Session for {$session->username} was NOT closed in database to maintain integrity. The user will be blocked from reconnecting until the modem is restarted or the NAS clears the stale session.");
        }
    }

    /**
     * Internal helper to close session in DB
     */
    private function performForceClose($session)
    {
        $session->update([
            'acctstoptime' => now(),
            'acctterminatecause' => 'Admin-Reset'
        ]);
    }
}
