<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NocController extends Controller
{
    public function index()
    {
        // Get Online Usernames from Radius
        $onlineUsernames = \App\Models\Radius\RadAcct::online()->pluck('username')->unique()->toArray();
        $onlineCount = count($onlineUsernames);

        // Get Total Active Customers (those who should be online)
        $activeCustomers = \App\Models\Customer::where('status', 'active')->count();
        $offlineCount = max(0, $activeCustomers - $onlineCount);

        // Placeholder data for NOC Dashboard
        $stats = [
            'total_ont' => \App\Models\Customer::whereNotNull('activated_at')->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'unconfigured' => 0, 
            'critical_signals' => 0,
            'open_tickets' => \App\Models\Ticket::where('status', 'open')->count(),
        ];

        return view('noc.index', compact('stats'));
    }

    public function discovery()
    {
        return view('noc.discovery');
    }

    public function signals()
    {
        return view('noc.signals');
    }
}
