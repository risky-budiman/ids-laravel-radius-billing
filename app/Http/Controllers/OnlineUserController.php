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
        
        // Find NAS for this session
        $nas = Nas::where('shortname', $session->nasipaddress)
                  ->orWhere('nasname', $session->nasipaddress)
                  ->first();
        
        if (!$nas) {
            // Fallback: If NAS not in DB, just force close
            $this->performForceClose($session);
            return back()->with('success', "NAS not found. Session for {$session->username} has been force closed in database.");
        }

        $coa = new RadiusCoAService();
        $success = $coa->disconnect($nas->nasname, $nas->secret, $session->username, $session->acctsessionid);

        if ($success) {
            return back()->with('success', "Disconnect signal sent to Router for {$session->username}.");
        } else {
            // Fallback: If CoA fails (NAS Offline), force close in DB
            $this->performForceClose($session);
            return back()->with('success', "NAS Unreachable. Session for {$session->username} has been automatically force closed in database.");
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
