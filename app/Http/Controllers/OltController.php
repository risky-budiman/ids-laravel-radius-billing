<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Services\Network\SnmpService;
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
            'snmp_read_community' => 'required|string',
            'snmp_write_community' => 'required|string',
            'telnet_port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
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
            'snmp_read_community' => 'required|string',
            'snmp_write_community' => 'required|string',
            'telnet_port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        // Only update password if filled
        if (empty($validated['password'])) {
            unset($validated['password']);
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
        try {
            $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
            $result = $snmp->testConnection();

            if ($result['status']) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully connected to OLT. Device Name: " . $result['device_name']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Connection failed: " . $result['message']
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage()
            ]);
        }
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
}
