<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Services\Network\OltGateway;
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
            'snmp_write_community' => 'nullable|string',
            'telnet_port' => 'nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['snmp_write_community'] = $validated['snmp_write_community'] ?? 'private';
        $validated['telnet_port'] = $validated['telnet_port'] ?? 23;
        $validated['is_active'] = $request->has('is_active');
        $olt = Olt::create($validated);

        return redirect()->route('olts.index')->with('success', 'OLT added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Olt $olt)
    {
        // Auto-discover and create ports on first view if none exist
        if ($olt->ponPorts()->count() === 0) {
            try {
                $gateway = new OltGateway($olt);
                $ports = $gateway->discoverPonPorts();
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
            } catch (\Exception $e) {
                \Log::warning("Auto discover on page load failed for OLT {$olt->id}: " . $e->getMessage());
            }
        }

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
            'snmp_write_community' => 'nullable|string',
            'telnet_port' => 'nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['snmp_write_community'] = $validated['snmp_write_community'] ?? 'private';
        $validated['telnet_port'] = $validated['telnet_port'] ?? 23;
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
        $gateway = new OltGateway($olt);
        $snmpResult = $gateway->testSnmpConnection();

        return response()->json([
            'success' => $snmpResult['status'],
            'message' => $snmpResult['message'],
            'results' => [
                'snmp' => [
                    'success' => $snmpResult['status'],
                    'message' => $snmpResult['message'],
                    'device_name' => $snmpResult['device_name'] ?? 'Unknown'
                ]
            ]
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
            $gateway = new OltGateway($olt);
            $ports = $gateway->discoverPonPorts();

            if (empty($ports)) {
                return back()->with('error', 'No PON ports discovered via SNMP. Please check SNMP credentials and ensure OLT is reachable.');
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

            return back()->with('success', 'Successfully discovered and created ' . count($ports) . ' PON ports via SNMP.');
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
     * Show details for a specific PON port (Scan ONUs via SNMP immediately)
     */
    public function showPort(Olt $olt, OltPonPort $port)
    {
        $shelf = $port->shelf ?: 1;
        $slot = $port->slot;
        $pon_port = $port->pon_port;

        $onus = [];
        try {
            $gateway = new OltGateway($olt);
            $onus = $gateway->getOnusOnPort($shelf, $slot, $pon_port);

            // Store result in cache (expires in 10 minutes)
            \Illuminate\Support\Facades\Cache::put("olt_port_data_{$port->id}", $onus, now()->addMinutes(10));
            
            // Sync Port Status to Database
            $newStatus = count($onus) > 0 ? 'active' : 'inactive';
            $port->update(['status' => $newStatus]);

            // UPDATE NOC DATA (CustomerSignalCache)
            foreach ($onus as $onuData) {
                if (!empty($onuData['sn'])) {
                    $customer = \App\Models\Customer::where('olt_id', $olt->id)
                        ->where('onu_sn', $onuData['sn'])
                        ->first();

                    if ($customer) {
                        \App\Models\CustomerSignalCache::updateOrCreate(
                            ['customer_id' => $customer->id],
                            [
                                'onu_index' => $onuData['index'],
                                'rx_power' => is_numeric($onuData['signal']) ? $onuData['signal'] : null,
                                'status' => $onuData['status'],
                                'last_polled_at' => now(),
                            ]
                        );

                        if ($onuData['status'] === 'online' && $customer->onu_index !== $onuData['index']) {
                            $customer->update(['onu_index' => $onuData['index']]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error loading port data: " . $e->getMessage());
            $onus = \Illuminate\Support\Facades\Cache::get("olt_port_data_{$port->id}", []);
        }

        return view('olts.show_port', [
            'olt' => $olt,
            'port' => $port,
            'onus' => $onus,
            'isLoading' => false
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

    public function getPortData(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        $shelf = $port->shelf ?: 1;
        $slot = $port->slot;
        $pon_port = $port->pon_port;

        if ($request->has('fresh')) {
            \Illuminate\Support\Facades\Cache::forget("olt_port_data_{$port->id}");
        }

        $onus = \Illuminate\Support\Facades\Cache::remember("olt_port_data_{$port->id}", now()->addMinutes(5), function() use ($olt, $shelf, $slot, $pon_port, $port) {
            try {
                $gateway = new OltGateway($olt);
                $data = $gateway->getOnusOnPort($shelf, $slot, $pon_port);
                $port->update(['status' => count($data) > 0 ? 'active' : 'inactive']);
                return $data;
            } catch (\Exception $e) {
                return [];
            }
        });
        
        return response()->json([
            'onus' => $onus ?: [],
            'status' => 'ready'
        ]);
    }
}
