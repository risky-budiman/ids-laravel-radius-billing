<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Radius\RadAcct;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use Illuminate\Http\Request;

class OnlineUserController extends Controller
{
    /**
     * List online / active RADIUS sessions.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'online');
        $search = $request->input('search');

        if ($status === 'offline') {
            $onlineUsernames = RadAcct::online()->pluck('username')->toArray();
            $query = Customer::with('package')->whereNotIn('username', $onlineUsernames);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
            }

            $paginator = $query->paginate(20);

            $data = $paginator->getCollection()->map(function ($c) {
                return [
                    'session_id' => null,
                    'username' => $c->username ?: ($c->customer_code ?: "user-{$c->id}"),
                    'customer_id' => (int) $c->id,
                    'customer_name' => $c->name ?: '-',
                    'customer_code' => $c->customer_code ?: '-',
                    'package_name' => $c->package ? $c->package->name : '-',
                    'is_online' => false,
                    'ip_address' => $c->pppoe_ip ?: null,
                    'mac_address' => null,
                    'nas_ip' => null,
                    'uptime_formatted' => 'Offline',
                    'duration_seconds' => 0,
                    'upload_mb' => 0.0,
                    'download_mb' => 0.0,
                    'start_time' => null,
                ];
            });

            return response()->json([
                'stats' => [
                    'online_count' => count($onlineUsernames),
                    'offline_count' => $paginator->total(),
                    'total_upload_gb' => 0,
                    'total_download_gb' => 0,
                ],
                'users' => $data,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]);
        }

        // Online Query
        $query = RadAcct::online()->orderBy('acctstarttime', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('framedipaddress', 'like', "%{$search}%")
                  ->orWhere('callingstationid', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(20);

        // Fetch matching customer names & packages
        $usernames = $paginator->pluck('username')->toArray();
        $customers = Customer::with('package')->whereIn('username', $usernames)->get()->keyBy('username');

        // Overall stats
        $totalOnline = RadAcct::online()->count();
        $totalUploadGb = round(RadAcct::online()->sum('acctinputoctets') / 1073741824, 2);
        $totalDownloadGb = round(RadAcct::online()->sum('acctoutputoctets') / 1073741824, 2);

        $data = $paginator->getCollection()->map(function ($s) use ($customers) {
            $c = $customers[$s->username] ?? null;
            $durationSec = $s->acctsessiontime ?: (now()->timestamp - ($s->acctstarttime ? $s->acctstarttime->timestamp : now()->timestamp));
            $hours = floor($durationSec / 3600);
            $minutes = floor(($durationSec % 3600) / 60);

            return [
                'session_id' => (int) $s->radacctid,
                'username' => $s->username,
                'customer_id' => $c ? (int) $c->id : null,
                'customer_name' => $c ? $c->name : $s->username,
                'customer_code' => $c ? $c->customer_code : '-',
                'package_name' => $c && $c->package ? $c->package->name : '-',
                'is_online' => true,
                'ip_address' => $s->framedipaddress,
                'mac_address' => $s->callingstationid,
                'nas_ip' => $s->nasipaddress,
                'uptime_formatted' => "{$hours}j {$minutes}m",
                'duration_seconds' => (int) $durationSec,
                'upload_mb' => round(($s->acctinputoctets ?? 0) / 1048576, 2),
                'download_mb' => round(($s->acctoutputoctets ?? 0) / 1048576, 2),
                'start_time' => $s->acctstarttime ? $s->acctstarttime->toIso8601String() : null,
            ];
        });

        return response()->json([
            'stats' => [
                'online_count' => $totalOnline,
                'offline_count' => Customer::count() - $totalOnline,
                'total_upload_gb' => $totalUploadGb,
                'total_download_gb' => $totalDownloadGb,
            ],
            'users' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Kick active session via CoA Disconnect.
     */
    public function kick(Request $request, $id)
    {
        $session = RadAcct::findOrFail($id);

        $nas = Nas::where('shortname', $session->nasipaddress)
            ->orWhere('nasname', $session->nasipaddress)
            ->first() ?? Nas::first();

        if (!$nas) {
            return response()->json([
                'message' => 'NAS / Router tidak ditemukan.',
            ], 422);
        }

        try {
            $coa = new RadiusCoAService();
            $coa->disconnect($nas->nasname, $nas->secret, $session->username, $session->acctsessionid);

            return response()->json([
                'message' => "Sinyal CoA Disconnect berhasil dikirim untuk {$session->username}.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mengirim CoA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force close stale session in DB.
     */
    public function forceClose($id)
    {
        $session = RadAcct::findOrFail($id);

        $session->update([
            'acctstoptime' => now(),
            'acctterminatecause' => 'Admin-Reset',
        ]);

        return response()->json([
            'message' => "Sesi untuk {$session->username} berhasil ditutup paksa di database.",
        ]);
    }
}
