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

        // Get OLTs
        $olts = \App\Models\Olt::all();

        // Get Stats from Cache
        $unconfiguredCount = 0; // Still dynamic for now or we could cache it too
        $criticalCount = \App\Models\CustomerSignalCache::where('rx_power', '<', -27)->count();

        // Placeholder data for NOC Dashboard
        $stats = [
            'total_ont' => \App\Models\Customer::whereNotNull('activated_at')->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'unconfigured' => $unconfiguredCount,
            'critical_signals' => $criticalCount,
            'open_tickets' => \App\Models\Ticket::where('status', 'open')->count(),
        ];

        return view('noc.index', compact('stats', 'olts'));
    }

    public function discovery()
    {
        $activeOlts = \App\Models\Olt::where('is_active', true)->get();
        $discoveredOnus = [];

        foreach ($activeOlts as $olt) {
            $snmp = new \App\Services\Network\SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
            $discovery = new \App\Services\Network\OltDiscoveryService($snmp);
            
            $onus = $discovery->scanUnconfigured();
            
            foreach ($onus as $onu) {
                $onu['olt_name'] = $olt->name;
                $onu['olt_id'] = $olt->id;
                $discoveredOnus[] = $onu;
            }
        }

        return view('noc.discovery', compact('discoveredOnus'));
    }

    public function signals()
    {
        // Get customers with their cached signals
        $customers = \App\Models\Customer::whereNotNull('olt_id')
            ->whereNotNull('onu_index')
            ->with(['olt', 'signalCache'])
            ->get();

        return view('noc.signals', compact('customers'));
    }

    public function history($id)
    {
        $customer = \App\Models\Customer::with('olt')->findOrFail($id);
        
        $logs = \App\Models\CustomerSignalLog::where('customer_id', $id)
            ->orderBy('created_at', 'asc')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        return view('noc.history', compact('customer', 'logs'));
    }
}
