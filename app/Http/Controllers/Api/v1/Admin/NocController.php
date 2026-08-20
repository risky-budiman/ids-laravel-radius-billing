<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olt;
use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\Radius\RadAcct;
use App\Services\Network\OltGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NocController extends Controller
{
    /**
     * Get NOC summary overview and OLT hardware telemetry.
     */
    public function index()
    {
        $onlineCount = RadAcct::online()->distinct('username')->count('username');
        $activeCustomers = Customer::where('status', 'active')->count();
        $offlineCount = max(0, $activeCustomers - $onlineCount);

        $olts = Olt::where('is_active', true)->withCount('ponPorts')->get();

        $oltTelemetry = [];
        $unconfiguredList = [];

        foreach ($olts as $olt) {
            try {
                $gateway = new OltGateway($olt);
                $status = $gateway->getOltStatus();
                $oltTelemetry[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'ip_address' => $olt->ip_address,
                    'olt_type' => $olt->olt_type,
                    'ports_count' => $olt->pon_ports_count,
                    'is_online' => ($status['status'] ?? '') === 'online',
                    'cpu_usage' => $status['cpu'] ?? 0,
                    'temperature' => $status['temp'] ?? 0,
                    'uptime' => $status['uptime'] ?? 'N/A',
                ];

                $unconfigured = $gateway->scanUnconfiguredOnus();
                foreach ($unconfigured as $onu) {
                    $unconfiguredList[] = array_merge($onu, [
                        'olt_id' => $olt->id,
                        'olt_name' => $olt->name,
                    ]);
                }
            } catch (\Throwable $e) {
                $oltTelemetry[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'ip_address' => $olt->ip_address,
                    'olt_type' => $olt->olt_type,
                    'ports_count' => $olt->pon_ports_count,
                    'is_online' => false,
                    'cpu_usage' => 0,
                    'temperature' => 0,
                    'uptime' => 'Offline',
                ];
            }
        }

        $criticalCount = CustomerSignalCache::where('rx_power', '<', -27)->count();
        $warningCount = CustomerSignalCache::whereBetween('rx_power', [-27, -24])->count();
        $goodCount = CustomerSignalCache::where('rx_power', '>', -24)->count();

        return response()->json([
            'stats' => [
                'total_ont' => Customer::whereNotNull('activated_at')->count(),
                'online_ont' => $onlineCount,
                'offline_ont' => $offlineCount,
                'unconfigured_count' => count($unconfiguredList),
                'signals_good' => $goodCount,
                'signals_warning' => $warningCount,
                'signals_critical' => $criticalCount,
            ],
            'olts' => $oltTelemetry,
            'unconfigured_onus' => $unconfiguredList,
        ]);
    }

    /**
     * List all unconfigured ONUs discovered via SNMP across OLTs.
     */
    public function discovery()
    {
        $olts = Olt::where('is_active', true)->get();
        $unconfiguredList = [];

        foreach ($olts as $olt) {
            try {
                $gateway = new OltGateway($olt);
                $onus = $gateway->scanUnconfiguredOnus();
                foreach ($onus as $onu) {
                    $unconfiguredList[] = array_merge($onu, [
                        'olt_id' => $olt->id,
                        'olt_name' => $olt->name,
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'unconfigured_onus' => $unconfiguredList,
        ]);
    }

    /**
     * List customer signal health diagnostics.
     */
    public function signals(Request $request)
    {
        $query = CustomerSignalCache::with('customer.package');

        if ($request->input('filter') === 'critical') {
            $query->where('rx_power', '<', -27);
        } elseif ($request->input('filter') === 'warning') {
            $query->whereBetween('rx_power', [-27, -24]);
        } elseif ($request->input('filter') === 'good') {
            $query->where('rx_power', '>', -24);
        }

        $signals = $query->orderBy('rx_power', 'asc')->paginate(25);

        $data = $signals->getCollection()->map(function ($s) {
            return [
                'customer_id' => $s->customer_id,
                'customer_name' => $s->customer ? $s->customer->name : 'N/A',
                'customer_code' => $s->customer ? $s->customer->customer_code : '-',
                'rx_power' => (float) $s->rx_power,
                'tx_power' => (float) $s->tx_power,
                'status' => $s->status,
                'updated_at' => $s->updated_at ? $s->updated_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'signals' => $data,
            'pagination' => [
                'current_page' => $signals->currentPage(),
                'last_page' => $signals->lastPage(),
                'total' => $signals->total(),
            ]
        ]);
    }

    /**
     * Test connection to OLT via SNMP / Telnet.
     */
    public function testConnection($id)
    {
        $olt = Olt::findOrFail($id);

        try {
            $gateway = new OltGateway($olt);
            $status = $gateway->getOltStatus();

            return response()->json([
                'success' => ($status['status'] ?? '') === 'online',
                'message' => 'Koneksi SNMP ke OLT ' . $olt->name . ' Berhasil! Uptime: ' . ($status['uptime'] ?? 'N/A'),
                'telemetry' => $status,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke OLT gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
