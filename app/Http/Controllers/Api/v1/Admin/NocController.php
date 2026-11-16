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
    public function index(Request $request)
    {
        // Angka sama dengan dasbor web (NocService): online = pelanggan AKTIF yang punya sesi PPPoE
        $dash = app(\App\Services\Network\NocService::class)->dashboard();

        $olts = Olt::where('is_active', true)->withCount('ponPorts')->get();

        // Sama dengan dasbor web: cache 5 menit & OLT offline tidak dipindai (dulu dipindai setiap panggilan)
        $fresh = $request->boolean('refresh');
        $statuses = \App\Services\Network\NocSnapshot::oltStatuses($olts, $fresh);
        $unregistered = \Illuminate\Support\Facades\Cache::get('noc_unregistered');
        $unconfiguredList = $unregistered['onus'] ?? [];

        $oltTelemetry = [];
        foreach ($olts as $olt) {
            $status = $statuses[$olt->id] ?? ['status' => 'offline'];
            $online = ($status['status'] ?? '') === 'online';
            $oltTelemetry[] = [
                'id' => $olt->id,
                'name' => $olt->name,
                'ip_address' => $olt->ip_address,
                'olt_type' => $olt->olt_type,
                'ports_count' => $olt->pon_ports_count,
                'is_online' => $online,
                'cpu_usage' => $status['cpu'] ?? 0,
                'temperature' => $status['temp'] ?? 0,
                'uptime' => $online ? ($status['uptime'] ?? 'N/A') : 'Offline',
            ];
        }

        return response()->json([
            'stats' => [
                // Kunci lama dipertahankan untuk aplikasi staf
                'total_ont' => $dash['onu_tracked'],
                'online_ont' => $dash['pppoe_online'],
                'offline_ont' => $dash['pppoe_offline'],
                'unconfigured_count' => count($unconfiguredList),
                'unconfigured_unscanned' => collect($unregistered['olts'] ?? [])->where('state', '!=', 'ok')->pluck('name')->values()->all(),
                'signals_good' => $dash['signal_good'],
                'signals_warning' => $dash['signal_warn'],
                'signals_critical' => $dash['signal_critical'],
                'customers_active' => $dash['customers_active'],
                'onu_online' => $dash['onu_online'],
                'onu_down' => $dash['onu_down'],
                'onu_not_found' => $dash['onu_not_found'],
                'isolated_online' => $dash['isolated_online'],
                'last_sync_at' => $dash['last_sync_at'] ? date('c', $dash['last_sync_at']) : null,
                'unregistered_scanned_at' => isset($unregistered['at']) ? date('c', $unregistered['at']) : null,
            ],
            'olts' => $oltTelemetry,
            'unconfigured_onus' => $unconfiguredList,
        ]);
    }

    /**
     * List all unconfigured ONUs discovered via SNMP across OLTs.
     */
    public function discovery(Request $request)
    {
        // Sama dengan web: HSGQ lewat web OLT (menunggu izin), ZTE lewat SNMP; disimpan 5 menit
        $data = app(\App\Services\Network\NocService::class)->unregistered($request->boolean('refresh'));

        return response()->json([
            'unconfigured_onus' => $data['onus'],
            'olts' => $data['olts'],
            'scanned_at' => date('c', $data['at']),
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
     * Test connection to OLT via SNMP.
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
