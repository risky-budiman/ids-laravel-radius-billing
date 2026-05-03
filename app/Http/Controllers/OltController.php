<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Services\Network\SnmpService;
use App\Services\Network\OltDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OltController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $olts = Olt::withCount('ponPorts')->get();
        return view('olts.index', compact('olts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('olts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'required|integer',
            'snmp_version' => 'required|integer|in:1,2',
            'snmp_read_community' => 'required|string',
            'snmp_write_community' => 'required|string',
            'telnet_port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $olt = Olt::create($validated);

        return redirect()->route('olts.index')->with('success', 'OLT added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Olt $olt)
    {
        $olt->load('ponPorts');
        return view('olts.show', compact('olt'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Olt $olt)
    {
        return view('olts.edit', compact('olt'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'required|integer',
            'snmp_version' => 'required|integer|in:1,2',
            'snmp_read_community' => 'required|string',
            'snmp_write_community' => 'required|string',
            'telnet_port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        // Only update passwords if filled
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        
        if (empty($validated['enable_password'])) {
            unset($validated['enable_password']);
        }

        $olt->update($validated);

        return redirect()->route('olts.index')->with('success', 'OLT updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Olt $olt)
    {
        $olt->delete();
        return redirect()->route('olts.index')->with('success', 'OLT deleted successfully.');
    }

    /**
     * Test SNMP Connection.
     */
    public function testConnection(Olt $olt)
    {
        $results = [];

        // 1. Test SNMP
        try {
            $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port, $olt->snmp_version ?? 2);
            $snmpResult = $snmp->testConnection();
            $results['snmp'] = [
                'success' => $snmpResult['status'],
                'message' => $snmpResult['message'],
                'device_name' => $snmpResult['device_name'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            $results['snmp'] = ['success' => false, 'message' => $e->getMessage()];
        }

        // 2. Test Telnet
        try {
            $provisioning = new \App\Services\Network\ZteOltProvisioningService($olt);
            $telnetResult = $provisioning->testConnection();
            $results['telnet'] = [
                'success' => true,
                'message' => 'Connection Successful'
            ];
        } catch (\Exception $e) {
            $results['telnet'] = ['success' => false, 'message' => $e->getMessage()];
        }

        return response()->json([
            'success' => $results['snmp']['success'] && $results['telnet']['success'],
            'results' => $results
        ]);
    }

    /**
     * Generate PON Ports automatically.
     */
    public function generatePorts(Request $request, Olt $olt)
    {
        $request->validate([
            'slot' => 'required|integer',
            'port_count' => 'required|integer|in:8,16',
        ]);

        $slot = $request->slot;
        $count = $request->port_count;

        DB::beginTransaction();
        try {
            for ($i = 1; $i <= $count; $i++) {
                OltPonPort::updateOrCreate(
                    ['olt_id' => $olt->id, 'slot' => $slot, 'pon_port' => $i],
                    ['status' => 'active']
                );
            }
            DB::commit();
            return back()->with('success', "Generated {$count} PON ports for Slot {$slot}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Error generating ports: " . $e->getMessage());
        }
    }

    /**
     * Auto Discover PON Ports via SNMP.
     */
    public function autoDiscoverPorts(Olt $olt)
    {
        try {
            $ports = [];

            // 1. Try SNMP first
            try {
                $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port, $olt->snmp_version ?? 2);
                $discovery = new OltDiscoveryService($snmp);
                $ports = $discovery->discoverPonPorts();
            } catch (\Exception $e) {
                \Log::warning("SNMP Discovery failed for OLT {$olt->ip_address}, trying CLI fallback...");
            }

            // 2. Fallback to CLI (Telnet) if SNMP failed or returned nothing
            if (empty($ports)) {
                $provisioning = new \App\Services\Network\ZteOltProvisioningService($olt);
                $ports = $provisioning->discoverPortsViaCli();
                
                // For each port found via CLI, try to see if it has ONUs to set status
                foreach ($ports as &$p) {
                    $onus = $provisioning->getOnusOnPortViaCli($p['shelf'], $p['slot'], $p['port']);
                    $p['status'] = count($onus) > 0 ? 'active' : 'inactive';
                }
            }

            if (empty($ports)) {
                return back()->with('error', 'No PON ports discovered via SNMP or CLI. Please check your credentials and ensure OLT is supported.');
            }

            // Reset all ports to inactive first to ensure accuracy
            OltPonPort::where('olt_id', $olt->id)->update(['status' => 'inactive']);

            DB::beginTransaction();
            foreach ($ports as $port) {
                OltPonPort::updateOrCreate(
                    [
                        'olt_id' => $olt->id, 
                        'slot' => $port['slot'], 
                        'pon_port' => $port['port']
                    ],
                    [
                        'status' => $port['status'] ?? 'inactive', 
                        'description' => $port['description'] ?? "Port {$port['slot']}/{$port['port']}"
                    ]
                );
            }
            DB::commit();

            return back()->with('success', 'Successfully discovered and created ' . count($ports) . ' PON ports.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Discovery failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual Delete ONU from OLT
     */
    public function manualDeleteOnu(Request $request, Olt $olt)
    {
        $request->validate([
            'onu_index' => 'required|string',
        ]);

        $index = $request->onu_index;
        
        // This will dispatch the job we created earlier
        \App\Jobs\DeprovisionOnuJob::dispatch($olt->id, $index, "Manual Delete");

        return back()->with('success', "Deprovisioning job for ONU {$index} has been queued.");
    }
    /**
     * Show details for a specific PON port (Scan ONUs)
     */
    public function showPort(Olt $olt, OltPonPort $port)
    {
        // Clear old cached data to force a fresh "loading" state in the UI
        \Illuminate\Support\Facades\Cache::forget("olt_port_data_{$port->id}");

        // Dispatch job to fetch data in background
        \App\Jobs\FetchOltPortDataJob::dispatch($olt, $port);
        
        return view('olts.show_port', [
            'olt' => $olt,
            'port' => $port,
            'onus' => [],
            'isLoading' => true
        ]);
    }

    /**
     * Sync all PON ports status for an OLT
     */
    public function syncAllPorts(Olt $olt)
    {
        $ports = $olt->ponPorts;
        
        foreach ($ports as $index => $port) {
            // Dispatch with a slight delay for each port to avoid OLT lockout
            \App\Jobs\FetchOltPortDataJob::dispatch($olt, $port)
                ->delay(now()->addSeconds($index * 2));
        }

        return back()->with('success', "Syncing status for " . count($ports) . " ports in background. This may take a few minutes.");
    }

    public function getPortData(Olt $olt, \App\Models\OltPonPort $port)
    {
        $onus = \Illuminate\Support\Facades\Cache::get("olt_port_data_{$port->id}");
        
        return response()->json([
            'onus' => $onus ?: [],
            'status' => $onus !== null ? 'ready' : 'pending'
        ]);
    }
}
