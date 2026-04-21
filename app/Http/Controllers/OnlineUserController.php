<?php

namespace App\Http\Controllers;

use App\Models\Radius\RadAcct;
use Illuminate\Http\Request;

class OnlineUserController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'online'); // default to online
        
        $onlineUsernames = RadAcct::whereNull('acctstoptime')->pluck('username')->toArray();

        if ($status === 'offline') {
            $offlineUsers = \App\Models\Customer::with('package')
                ->whereNotIn('username', $onlineUsernames)
                ->paginate(20);
                
            return view('online-users.index', compact('offlineUsers', 'status'));
        }

        // Default: Online Users
        $onlineUsers = RadAcct::whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->paginate(20);
            
        return view('online-users.index', compact('onlineUsers', 'status'));
    }
}
