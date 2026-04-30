<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NocController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('refresh')) {
            \Illuminate\Support\Facades\Cache::forget('noc_olt_stats');
        }

        // Get Online Usernames from Radius
        $onlineUsernames = \App\Models\Radius\RadAcct::online()->pluck('username')->unique()->toArray();
        $onlineCount = count($onlineUsernames);

        // Get Total Active Customers (those who should be online)
        $activeCustomers = \App\Models\Customer::where('status', 'active')->count();
        $offlineCount = max(0, $activeCustomers - $onlineCount);

        // Get OLT Hardware Stats (Cached for 5 minutes)
        $olts = \App\Models\Olt::where('is_active', true)->get();
        $oltStats = \Illuminate\Support\Facades\Cache::remember('noc_olt_stats', 300, function() use ($olts) {
            $data = [];
            foreach ($olts as $olt) {
                try {
                    $service = new \App\Services\Network\ZteOltProvisioningService($olt);
                    $stats = $service->getOltStats();
                    $data[$olt->id] = $stats;
                } catch (\Exception $e) {
                    $data[$olt->id] = ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A'];
                }
            }
            return $data;
        });

        // Get Stats from Cache for Dashboard Boxes
        $unconfiguredCount = 0; 
        $criticalCount = \App\Models\CustomerSignalCache::where('rx_power', '<', -27)->count();

        $stats = [
            'total_ont' => \App\Models\Customer::whereNotNull('activated_at')->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'unconfigured' => $unconfiguredCount,
            'critical_signals' => $criticalCount,
            'open_tickets' => \App\Models\Ticket::where('status', 'open')->count(),
        ];

        return view('noc.index', compact('stats', 'olts', 'oltStats'));
    }

    public function discovery()
    {
        $activeOlts = \App\Models\Olt::where('is_active', true)->get();
        $discoveredOnus = [];

        foreach ($activeOlts as $olt) {
            $snmp = new \App\Services\Network\SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
            $discovery = new \App\Services\Network\OltDiscoveryService($snmp);
            
            $onus = $discovery->scanUnconfiguredOnus();
            
            foreach ($onus as $onu) {
                $onu['olt_name'] = $olt->name;
                $onu['olt_id'] = $olt->id;
                $discoveredOnus[] = $onu;
            }
        }

        return view('noc.discovery', compact('discoveredOnus'));
    }

    public function signals(Request $request)
    {
        // Get customers with their cached signals
        $query = \App\Models\Customer::whereNotNull('olt_id')
            ->whereNotNull('onu_index')
            ->with(['olt', 'signalCache']);

        // Handle filtering from Dashboard
        if ($request->get('filter') === 'critical') {
            $query->whereHas('signalCache', function($q) {
                $q->where('rx_power', '<', -27);
            });
        }

        $customers = $query->get();

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
