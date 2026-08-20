<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Region;
use App\Models\Sto;
use App\Models\Stb;
use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\User;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use App\Models\Radius\RadAcct;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * List customers with pagination, search, and filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Stats query
        $countQuery = Customer::query();
        if ($user->isMitra()) {
            $countQuery->where('partner_id', $user->id);
        }

        $stats = [
            'total' => $countQuery->clone()->count(),
            'active' => $countQuery->clone()->where('status', Customer::STATUS_ACTIVE)->count(),
            'waiting_activation' => $countQuery->clone()->where('status', Customer::STATUS_WAITING_ACTIVATION)->count(),
            'suspended' => $countQuery->clone()->where('status', Customer::STATUS_SUSPENDED)->count(),
            'new' => $countQuery->clone()->where('status', Customer::STATUS_NEW)->count(),
        ];

        // 2. Customers query
        $query = Customer::with(['package', 'signalCache', 'olt']);

        if ($user->isMitra()) {
            $query->where('partner_id', $user->id);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Package Filter
        if ($request->filled('package_id') && $request->input('package_id') != 0) {
            $query->where('package_id', $request->input('package_id'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        // Fetch passwords from RadCheck
        $usernames = $paginator->pluck('username')->toArray();
        $passwords = RadCheck::whereIn('username', $usernames)
            ->where('attribute', 'Cleartext-Password')
            ->pluck('value', 'username');

        // Check online sessions
        $onlineUsernames = RadAcct::whereIn('username', $usernames)
            ->whereNull('acctstoptime')
            ->pluck('username')
            ->toArray();

        $data = $paginator->getCollection()->map(function ($c) use ($passwords, $onlineUsernames) {
            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'username' => $c->username,
                'password' => $passwords[$c->username] ?? $c->password ?? '-',
                'phone' => $c->phone,
                'email' => $c->email,
                'address' => $c->address,
                'status' => $c->status,
                'is_active' => (bool) $c->is_active,
                'is_online' => in_array($c->username, $onlineUsernames),
                'package_id' => $c->package_id,
                'package_name' => $c->package ? $c->package->name : null,
                'package_price' => $c->package ? (float) $c->package->price : null,
                'package_speed' => $c->package ? $c->package->download_speed : null,
                'rx_power' => $c->signalCache ? $c->signalCache->rx_power : null,
                'olt_name' => $c->olt ? $c->olt->name : null,
                'onu_sn' => $c->onu_sn,
                'current_month_usage_gb' => (float) ($c->current_month_usage_gb ?? 0),
                'created_at' => $c->created_at ? $c->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'stats' => $stats,
            'customers' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Get single customer detail.
     */
    public function show($id)
    {
        $customer = Customer::with([
            'package',
            'olt',
            'odc',
            'odp',
            'partner',
            'sales',
            'signalCache'
        ])->findOrFail($id);

        // Fetch cleartext password
        $cleartextPassword = RadCheck::where('username', $customer->username)
            ->where('attribute', 'Cleartext-Password')
            ->value('value') ?? $customer->password;

        // Fetch recent RADIUS sessions
        $sessions = RadAcct::where('username', $customer->username)
            ->orderBy('acctstarttime', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($s) {
                $duration = $s->acctsessiontime ?? 0;
                return [
                    'session_id' => $s->radacctid,
                    'start_time' => $s->acctstarttime ? $s->acctstarttime->toIso8601String() : null,
                    'stop_time' => $s->acctstoptime ? $s->acctstoptime->toIso8601String() : null,
                    'is_active' => is_null($s->acctstoptime),
                    'ip_address' => $s->framedipaddress,
                    'mac_address' => $s->callingstationid,
                    'duration_seconds' => $duration,
                    'upload_mb' => round(($s->acctinputoctets ?? 0) / 1048576, 2),
                    'download_mb' => round(($s->acctoutputoctets ?? 0) / 1048576, 2),
                    'terminate_cause' => $s->acctterminatecause,
                ];
            });

        $activeSession = $sessions->firstWhere('is_active', true);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'username' => $customer->username,
                'password' => $cleartextPassword,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'ktp' => $customer->ktp,
                'address' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'status' => $customer->status,
                'is_active' => (bool) $customer->is_active,
                'customer_type' => $customer->customer_type,
                'billing_type' => $customer->billing_type,
                'billing_method' => $customer->billing_method,
                'billing_day' => $customer->billing_day,
                'billing_due_day' => $customer->billing_due_day,
                'billing_next_date' => $customer->billing_next_date ? $customer->billing_next_date->format('Y-m-d') : null,
                'billing_due_date' => $customer->billing_due_date ? $customer->billing_due_date->format('Y-m-d') : null,
                'activated_at' => $customer->activated_at ? $customer->activated_at->toIso8601String() : null,
                'installation_fee' => (float) ($customer->installation_fee ?? 0),
                'package' => $customer->package ? [
                    'id' => $customer->package->id,
                    'name' => $customer->package->name,
                    'price' => (float) $customer->package->price,
                    'speed' => $customer->package->download_speed,
                    'enable_fup' => (bool) $customer->package->enable_fup,
                    'fup_limit_gb' => (float) ($customer->package->fup_limit_gb ?? 0),
                ] : null,
                'current_month_usage_gb' => (float) ($customer->current_month_usage_gb ?? 0),
                'olt' => $customer->olt ? [
                    'id' => $customer->olt->id,
                    'name' => $customer->olt->name,
                    'ip_address' => $customer->olt->ip_address,
                ] : null,
                'onu_sn' => $customer->onu_sn,
                'onu_index' => $customer->onu_index,
                'onu_type' => $customer->onu_type,
                'signal' => $customer->signalCache ? [
                    'rx_power' => $customer->signalCache->rx_power,
                    'tx_power' => $customer->signalCache->tx_power,
                    'status' => $customer->signalCache->status,
                    'updated_at' => $customer->signalCache->updated_at ? $customer->signalCache->updated_at->toIso8601String() : null,
                ] : null,
                'odc' => $customer->odc ? ['id' => $customer->odc->id, 'name' => $customer->odc->name] : null,
                'odp' => $customer->odp ? ['id' => $customer->odp->id, 'name' => $customer->odp->name] : null,
                'odp_port' => $customer->odp_port,
                'cable_length' => $customer->cable_length,
                'partner' => $customer->partner ? ['id' => $customer->partner->id, 'name' => $customer->partner->name] : null,
                'sales' => $customer->sales ? ['id' => $customer->sales->id, 'name' => $customer->sales->name] : null,
                'is_online' => (bool) $activeSession,
                'description' => $customer->description,
                'created_at' => $customer->created_at ? $customer->created_at->toIso8601String() : null,
            ],
            'sessions' => $sessions,
        ]);
    }

    /**
     * Store new customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:64|unique:customers,username|unique:radcheck,username',
            'password' => 'required|string|min:6',
            'customer_code' => 'required|string|max:20|unique:customers,customer_code',
            'region_code' => 'nullable|string|max:10',
            'sto_code' => 'nullable|string|max:10',
            'stb_code' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'ktp' => 'nullable|string|max:20|unique:customers,ktp',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
            'customer_type' => 'required|in:personal,corporate,vip',
            'installation_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string|max:64',
            'onu_index' => 'nullable|string|max:64',
            'odc_id' => 'nullable|string',
            'odp_id' => 'nullable|string',
            'odp_port' => 'nullable|integer',
            'partner_id' => 'nullable|exists:users,id',
            'sales_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);

        $customer = DB::transaction(function () use ($validated, $isActive) {
            $customer = Customer::create(array_merge($validated, [
                'is_active' => $isActive,
                'status' => $isActive ? Customer::STATUS_ACTIVE : Customer::STATUS_WAITING_ACTIVATION,
                'activated_at' => $isActive ? now() : null,
            ]));

            if ($isActive) {
                $customer->syncBillingDates();

                // Add to RADIUS
                RadCheck::create([
                    'username' => $customer->username,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $validated['password'],
                ]);

                $package = Package::find($validated['package_id']);
                if ($package) {
                    RadUserGroup::create([
                        'username' => $customer->username,
                        'groupname' => $package->name,
                        'priority' => 1,
                    ]);
                }
            }

            return $customer;
        });

        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan',
            'customer_id' => $customer->id,
        ], 201);
    }

    /**
     * Update customer.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
            'customer_type' => 'required|in:personal,corporate,vip',
            'installation_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string|max:64',
            'onu_index' => 'nullable|string|max:64',
            'odc_id' => 'nullable|string',
            'odp_id' => 'nullable|string',
            'odp_port' => 'nullable|integer',
            'partner_id' => 'nullable|exists:users,id',
            'sales_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($customer, $validated, $request) {
            $customer->update($validated);

            // Update password in RADIUS if provided
            if ($request->filled('password')) {
                RadCheck::updateOrCreate(
                    ['username' => $customer->username, 'attribute' => 'Cleartext-Password'],
                    ['op' => ':=', 'value' => $request->input('password')]
                );
            }

            // Update Package group if changed
            if ($request->filled('package_id')) {
                $package = Package::find($validated['package_id']);
                if ($package) {
                    RadUserGroup::updateOrCreate(
                        ['username' => $customer->username],
                        ['groupname' => $package->name, 'priority' => 1]
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Data pelanggan berhasil diperbarui',
        ]);
    }

    /**
     * Delete customer.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        DB::transaction(function () use ($customer) {
            // Remove from RADIUS
            RadCheck::where('username', $customer->username)->delete();
            RadUserGroup::where('username', $customer->username)->delete();

            // Kick active session
            $nas = Nas::first();
            if ($nas) {
                app(RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
            }

            $customer->delete();
        });

        return response()->json([
            'message' => 'Pelanggan berhasil dihapus',
        ]);
    }

    /**
     * Bulk Action on Customers.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,suspend,dismantle,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:customers,id',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        $customers = Customer::whereIn('id', $ids)->get();
        $count = 0;
        $nas = Nas::first();

        foreach ($customers as $customer) {
            switch ($action) {
                case 'activate':
                    $customer->update([
                        'is_active' => true,
                        'status' => Customer::STATUS_ACTIVE,
                        'activated_at' => now(),
                    ]);
                    $customer->syncBillingDates();
                    $count++;
                    break;

                case 'suspend':
                    $customer->update([
                        'is_active' => false,
                        'status' => Customer::STATUS_SUSPENDED,
                    ]);
                    if ($nas) {
                        app(RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
                    }
                    $count++;
                    break;

                case 'dismantle':
                    $customer->update([
                        'status' => Customer::STATUS_WAITING_DISMANTLE,
                    ]);
                    $count++;
                    break;

                case 'delete':
                    RadCheck::where('username', $customer->username)->delete();
                    RadUserGroup::where('username', $customer->username)->delete();
                    if ($nas) {
                        app(RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
                    }
                    $customer->delete();
                    $count++;
                    break;
            }
        }

        return response()->json([
            'message' => "$count pelanggan berhasil diproses.",
        ]);
    }

    /**
     * Reset FUP Usage.
     */
    public function resetFup($id)
    {
        $customer = Customer::findOrFail($id);

        $customer->update([
            'current_month_usage_gb' => 0,
            'last_usage_sync' => now(),
        ]);

        // Kick session to re-apply high-speed profile
        $nas = Nas::first();
        if ($nas) {
            app(RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
        }

        return response()->json([
            'message' => "FUP pelanggan {$customer->name} berhasil direset.",
        ]);
    }

    /**
     * Form master data for dropdowns (packages, regions, stos, stbs, olts, odcs, odps).
     */
    public function formData()
    {
        $packages = Package::where('is_active', true)->get()->map(function ($p) {
            return [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'price' => (float) $p->price,
                'download_speed' => (string) ($p->download_speed ?? ''),
            ];
        });

        $regions = Region::get()->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'code' => (string) ($r->code ?? (string) $r->id),
                'name' => (string) $r->name,
            ];
        });

        $stos = Sto::with('region')->get()->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'code' => (string) ($s->code ?? (string) $s->id),
                'name' => (string) $s->name,
                'region_code' => (string) ($s->region->code ?? ''),
            ];
        });

        $stbs = Stb::with('sto')->get()->map(function ($sb) {
            return [
                'id' => (int) $sb->id,
                'code' => (string) ($sb->code ?? (string) $sb->id),
                'name' => (string) $sb->name,
                'sto_code' => (string) ($sb->sto->code ?? ''),
            ];
        });

        $olts = Olt::where('is_active', true)->get()->map(function ($o) {
            return [
                'id' => (int) $o->id,
                'name' => (string) $o->name,
                'ip_address' => (string) ($o->ip_address ?? ''),
            ];
        });

        $odcs = Odc::get()->map(function ($odc) {
            return [
                'id' => (int) $odc->id,
                'name' => (string) $odc->name,
                'code' => (string) ($odc->code ?? ''),
            ];
        });

        $odps = Odp::get()->map(function ($odp) {
            return [
                'id' => (int) $odp->id,
                'name' => (string) $odp->name,
                'code' => (string) ($odp->code ?? ''),
            ];
        });

        $partners = User::where('role', User::ROLE_MITRA)->get()->map(function ($u) {
            return [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
            ];
        });

        $sales = User::where('role', User::ROLE_SALES)->orWhere('is_sales', true)->get()->map(function ($u) {
            return [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
            ];
        });

        return response()->json([
            'packages' => $packages,
            'regions' => $regions,
            'stos' => $stos,
            'stbs' => $stbs,
            'olts' => $olts,
            'odcs' => $odcs,
            'odps' => $odps,
            'partners' => $partners,
            'sales' => $sales,
        ]);
    }
}
