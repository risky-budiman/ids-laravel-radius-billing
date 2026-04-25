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
        
        // Find NAS for this session to get the secret
        $nas = Nas::where('shortname', $session->nasipaddress)
                  ->orWhere('nasname', $session->nasipaddress)
                  ->first();
        
        if (!$nas) {
            return back()->with('error', 'NAS/Router not found in database. Cannot send kick command.');
        }

        $coa = new RadiusCoAService();
        $success = $coa->disconnect($nas->nasname, $nas->secret, $session->username);

        if ($success) {
            return back()->with('success', "Kick command sent to {$session->username}. User will be disconnected shortly.");
        } else {
            return back()->with('error', "Failed to send kick command (NAS Unreachable). Use 'Force Close' if NAS is offline.");
        }
    }

    public function forceClose(Request $request, $radacctid)
    {
        $session = RadAcct::findOrFail($radacctid);
        
        $session->update([
            'acctstoptime' => now(),
            'acctterminatecause' => 'Admin-Reset'
        ]);

        return back()->with('success', "Session for {$session->username} has been force closed in database.");
    }
}
