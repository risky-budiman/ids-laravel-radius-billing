<?php

namespace App\Http\Controllers;

use App\Services\Network\OltGateway;
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

        // Get OLT Hardware Stats via SNMP (Cached for 5 minutes)
        $olts = \App\Models\Olt::where('is_active', true)->get();
        $oltStats = \Illuminate\Support\Facades\Cache::remember('noc_olt_stats', 300, function() use ($olts) {
            $data = [];
            foreach ($olts as $olt) {
                try {
                    $gateway = new OltGateway($olt);
                    $data[$olt->id] = $gateway->getOltStatus();
                } catch (\Exception $e) {
                    $data[$olt->id] = ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
                }
            }
            return $data;
        });

        // Get Unconfigured ONU Count via SNMP
        $unconfiguredCount = 0;
        foreach ($olts as $olt) {
            try {
                $gateway = new OltGateway($olt);
                $unconfiguredCount += count($gateway->scanUnconfiguredOnus());
            } catch (\Exception $e) { }
        }
        
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

    public function discovery(Request $request)
    {
        $lastRun = \Illuminate\Support\Facades\Cache::get('noc_discovery_last_run');
        
        // Robust fix for "incomplete object" or serialization issues
        if ($lastRun) {
            try {
                if (!($lastRun instanceof \Illuminate\Support\Carbon)) {
                    $lastRun = \Illuminate\Support\Carbon::parse($lastRun);
                }
            } catch (\Throwable $e) {
                // If it's a corrupt object, just ignore it and treat as never run
                $lastRun = null;
                \Illuminate\Support\Facades\Cache::forget('noc_discovery_last_run');
            }
        }
        $discoveredOnus = \Illuminate\Support\Facades\Cache::get('noc_discovered_onus', []);
        $isRunning = \Illuminate\Support\Facades\Cache::has('noc_discovery_running');

        if ($request->has('refresh') || (!$lastRun && !$isRunning)) {
            \Illuminate\Support\Facades\Log::info("Dispatching ScanOltDiscoveryJob...");
            \Illuminate\Support\Facades\Cache::forget('noc_discovery_running'); // Force clear before starting
            \Illuminate\Support\Facades\Cache::put('noc_discovery_running', true, 600);
            \App\Jobs\ScanOltDiscoveryJob::dispatch();
            
            if ($request->ajax()) {
                return response()->json(['status' => 'started']);
            }
            
            return redirect()->route('noc.discovery')->with('status', 'Pemindaian dimulai di latar belakang. Silakan tunggu beberapa saat.');
        }

        if ($request->ajax()) {
            return response()->json([
                'onus' => $discoveredOnus,
                'last_run' => $lastRun ? $lastRun->diffForHumans() : 'Never',
                'is_running' => $isRunning
            ]);
        }

        return view('noc.discovery', compact('discoveredOnus', 'lastRun', 'isRunning'));
    }

    public function signals(Request $request)
    {
        $isRunning = \Illuminate\Support\Facades\Cache::has('noc_signals_running');

        if ($request->has('refresh') && !$isRunning) {
            \App\Jobs\SyncOltSignalsJob::dispatch();
            
            if ($request->ajax()) {
                return response()->json(['status' => 'started']);
            }
            
            return redirect()->back()->with('status', 'Sinkronisasi sinyal sedang berjalan di latar belakang.');
        }

        // Get customers with their cached signals
        // Show customers who have either an index OR a serial number
        $query = \App\Models\Customer::whereNotNull('olt_id')
            ->where(function($q) {
                $q->whereNotNull('onu_index')
                  ->orWhereNotNull('onu_sn');
            })
            ->with(['olt', 'signalCache']);

        // Handle filtering from Dashboard
        if ($request->get('filter') === 'critical') {
            $query->whereHas('signalCache', function($q) {
                $q->where('rx_power', '<', -27);
            });
        }

        $customers = $query->get();

        if ($request->ajax()) {
            return response()->json([
                'customers' => $customers,
                'is_running' => $isRunning
            ]);
        }

        return view('noc.signals', compact('customers', 'isRunning'));
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
