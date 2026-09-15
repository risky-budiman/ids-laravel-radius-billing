<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // 1. Base query for stats counts (subject only to Mitra visibility restriction)
        $countQuery = Customer::query();
        if (auth()->user()->isMitra()) {
            $countQuery->where('partner_id', auth()->id());
        }

        $stats = [
            'total' => $countQuery->clone()->count(),
            'active' => $countQuery->clone()->where('status', Customer::STATUS_ACTIVE)->count(),
            'waiting_activation' => $countQuery->clone()->where('status', Customer::STATUS_WAITING_ACTIVATION)->count(),
            'suspended' => $countQuery->clone()->where('status', Customer::STATUS_SUSPENDED)->count(),
        ];

        // 2. Query for customers list with eager loading
        $query = Customer::with('package');
        
        if (auth()->user()->isMitra()) {
            $query->where('partner_id', auth()->id());
        }

        // Apply Search Filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Apply Package Filter
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->input('package_id'));
        }

        // Order by latest created date first
        $query->orderBy('created_at', 'desc');

        $customers = $query->paginate(10);
        
        // Fetch passwords for these customers from radcheck
        $usernames = $customers->pluck('username')->toArray();
        $passwords = \App\Models\Radius\RadCheck::whereIn('username', $usernames)
            ->where('attribute', 'Cleartext-Password')
            ->get()
            ->pluck('value', 'username');

        foreach ($customers as $customer) {
            $customer->cleartext_password = $passwords[$customer->username] ?? $customer->password ?? '-';
        }

        $packages = Package::where('is_active', true)->get();

        return view('customers.index', compact('customers', 'stats', 'packages'));
    }

    /**
     * Handle bulk actions for selected customers
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || empty($action)) {
            return redirect()->back()->with('error', 'Silakan pilih pelanggan dan aksi yang ingin dilakukan.');
        }

        $customers = Customer::whereIn('id', $ids)->get();
        $count = 0;

        switch ($action) {
            case 'activate':
                abort_if(auth()->user()->isSales(), 403, 'Unauthorized');
                foreach ($customers as $customer) {
                    if (in_array($customer->status, [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION])) {
                        $customer->update([
                            'is_active' => true,
                            'status' => Customer::STATUS_ACTIVE,
                            'activated_at' => now(),
                        ]);
                        $customer->syncBillingDates();
                        
                        // Sync status in radius if needed
                        $nas = \App\Models\Radius\Nas::first();
                        if ($nas) {
                            app(\App\Services\RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
                        }

                        $count++;
                    }
                }
                $message = "$count pelanggan berhasil diaktifkan secara massal.";
                break;

            case 'suspend':
                abort_if(auth()->user()->isSales(), 403, 'Unauthorized');
                foreach ($customers as $customer) {
                    if ($customer->is_active) {
                        $customer->update([
                            'is_active' => false,
                            'status' => Customer::STATUS_SUSPENDED,
                        ]);
                        
                        // CoA Disconnect
                        $nas = \App\Models\Radius\Nas::first();
                        if ($nas) {
                            app(\App\Services\RadiusCoAService::class)->disconnect($nas->nasname, $nas->secret, $customer->username);
                        }

                        $count++;
                    }
                }
                $message = "$count pelanggan berhasil di-suspend secara massal.";
                break;

            case 'dismantle':
                abort_if(auth()->user()->isSales(), 403, 'Unauthorized');
                foreach ($customers as $customer) {
                    if ($customer->status !== Customer::STATUS_WAITING_DISMANTLE && $customer->status !== Customer::STATUS_DISMANTLED) {
                        $customer->update([
                            'status' => Customer::STATUS_WAITING_DISMANTLE,
                        ]);
                        $count++;
                    }
                }
                $message = "$count pelanggan berhasil diajukan dismantle secara massal.";
                break;

            case 'delete':
                abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized');
                foreach ($customers as $customer) {
                    $customer->delete();
                    $count++;
                }
                $message = "$count pelanggan berhasil dihapus secara massal.";
                break;

            default:
                return redirect()->back()->with('error', 'Aksi tidak dikenal.');
        }

        return redirect()->back()->with('success', $message);
    }

    public function map()
    {
        // 1. Get Customers
        $query = Customer::where('latitude', '!=', '')
            ->whereNotNull('latitude')
            ->with(['package', 'odp']);

        if (auth()->user()->isMitra()) {
            $query->where('partner_id', auth()->id());
        }

        $customers = $query->get();

        // 2. Get Infrastructure (Only for Admin/Technical)
        $infrastructure = [];
        if (auth()->user()->isAdmin() || auth()->user()->isTeknisi()) {
            $infrastructure = [
                'regions' => \App\Models\Region::whereNotNull('latitude')->get(),
                'olts' => \App\Models\Olt::whereNotNull('latitude')->get(),
                'stos' => \App\Models\Sto::whereNotNull('latitude')->with('region')->get(),
                'stbs' => \App\Models\Stb::whereNotNull('latitude')->with('sto')->get(),
                'odcs' => \App\Models\Odc::whereNotNull('latitude')->with('stb')->get(),
                'odps' => \App\Models\Odp::whereNotNull('latitude')->with('odc')->get(),
            ];
        }
            
        return view('customers.map', compact('customers', 'infrastructure'));
    }

    public function show(Customer $customer)
    {
        if (auth()->user()->isMitra() && $customer->partner_id !== auth()->id()) {
            abort(403);
        }
        $customer->load('package');
        
        // Fetch recent session history from RADIUS
        $sessions = \App\Models\Radius\RadAcct::where('username', $customer->username)
            ->orderBy('acctstarttime', 'desc')
            ->limit(50)
            ->get();

        return view('customers.show', compact('customer', 'sessions'));
    }

    public function create(Request $request)
    {
        $packages = Package::where('is_active', true)->get();
        $regions = \App\Models\Region::all();
        $stos = \App\Models\Sto::all();
        $stbs = \App\Models\Stb::all();
        $olts = \App\Models\Olt::where('is_active', true)->get();
        $odcs = \App\Models\Odc::all();
        $odps = \App\Models\Odp::all();
        $partners = User::where('role', User::ROLE_MITRA)->get();
        $sales = User::where('role', User::ROLE_SALES)->orWhere('is_sales', true)->get();
        return view('customers.create', compact('packages', 'regions', 'stos', 'stbs', 'olts', 'odcs', 'odps', 'partners', 'sales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:64|unique:customers,username|unique:radcheck,username',
            'password' => 'required|string|min:6',
            'name' => 'required|string|max:255',
            'region_code' => 'required|string|max:10|exists:regions,code',
            'sto_code' => 'required|string|max:10|exists:stos,code',
            'stb_code' => 'required|string|max:10|exists:stbs,code',
            'email' => 'nullable|email|max:255',
            'ktp' => 'nullable|string|max:20|unique:customers,ktp',
            'customer_code' => 'required|string|max:20|unique:customers,customer_code',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'is_active' => 'nullable',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
            'customer_type' => 'required|in:personal,corporate,vip',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'installation_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'partner_id' => 'nullable|exists:users,id',
            'commission_rate' => 'nullable|numeric|min:0',
            'commission_type' => 'nullable|in:percentage,fixed',
            'sales_id' => 'nullable|exists:users,id',
            'sales_commission_rate' => 'nullable|numeric|min:0',
            'sales_commission_type' => 'nullable|in:percentage,fixed',
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string|max:64',
            'onu_index' => 'nullable|string|max:64',
            'onu_type' => 'nullable|string|max:64',
            'scheduled_activation_at' => 'nullable|date',
            'odc_id' => 'nullable|string|exists:odcs,id',
            'odp_id' => 'nullable|string|exists:odps,id',
            'odp_port' => 'nullable|integer',
            'cable_length' => 'nullable|integer',
            'vlan_id' => 'nullable|integer',
            'static_ip' => 'nullable|string',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'cpe_brand' => 'nullable|string',
            'cpe_model' => 'nullable|string',
            'cpe_mac' => 'nullable|string',
        ]);

        $package = Package::find($validated['package_id']);
        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);
        $scheduledAt = $validated['scheduled_activation_at'] ?? null;

        DB::transaction(function () use ($validated, $package, $isActive, $scheduledAt, $request) {
            // Handle File Uploads
            $photos = [];
            foreach(['identity_photo', 'house_photo', 'cpe_photo'] as $field) {
                if ($request->hasFile($field)) {
                    $photos[$field] = $request->file($field)->store('customers/kyc', 'public');
                }
            }

            // Create in Billing
            $customer = Customer::create(array_merge([
                'customer_code' => $validated['customer_code'],
                'region_code' => $validated['region_code'] ?? '000',
                'sto_code' => $validated['sto_code'] ?? '000',
                'stb_code' => $validated['stb_code'] ?? '000',
                'username' => $validated['username'],
                'password' => $validated['password'],
                'name' => $validated['name'],
                'ktp' => $validated['ktp'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'package_id' => $validated['package_id'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'is_active' => $isActive,
                'status' => $isActive ? Customer::STATUS_ACTIVE : Customer::STATUS_NEW,
                'billing_type' => $validated['billing_type'],
                'billing_method' => $validated['billing_method'],
                'billing_day' => $validated['billing_day'] ?? 1,
                'billing_due_day' => $validated['billing_due_day'] ?? 20,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'scheduled_activation_at' => $scheduledAt,
                'installation_fee' => $validated['installation_fee'] ?? 0,
                'use_tax' => $request->has('use_tax'),
                'olt_id' => $validated['olt_id'] ?? null,
                'onu_sn' => $validated['onu_sn'] ?? null,
                'onu_index' => $validated['onu_index'] ?? null,
                'onu_type' => $validated['onu_type'] ?? null,
                // Enterprise
                'customer_type' => $validated['customer_type'] ?? 'personal',
                'odc_id' => $validated['odc_id'] ?? null,
                'odp_id' => $validated['odp_id'] ?? null,
                'odp_port' => $validated['odp_port'] ?? null,
                'cable_length' => $validated['cable_length'] ?? null,
                'vlan_id' => $validated['vlan_id'] ?? null,
                'static_ip' => $validated['static_ip'] ?? null,
                'cpe_brand' => $validated['cpe_brand'] ?? null,
                'cpe_model' => $validated['cpe_model'] ?? null,
                'cpe_mac' => $validated['cpe_mac'] ?? null,
                'description' => $validated['description'] ?? null,
                'partner_id' => $validated['partner_id'] ?? null,
                'commission_rate' => $validated['commission_rate'] ?? null,
                'commission_type' => $validated['commission_type'] ?? null,
                'sales_id' => $validated['sales_id'] ?? null,
                'sales_commission_rate' => $validated['sales_commission_rate'] ?? null,
                'sales_commission_type' => $validated['sales_commission_type'] ?? null,
            ], $photos));

            // NEW: Auto-Provisioning on OLT if SN is provided
            if ($customer->olt_id && $customer->onu_sn) {
                \App\Jobs\ProvisionOnuJob::dispatch($customer->id);
            }

            // Create in RADIUS (Authentication)
            RadCheck::create([
                'username' => $validated['username'],
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $validated['password'],
            ]);

            // Create in RADIUS (Authorization/Package assignment)
            if ($package) {
                RadUserGroup::create([
                    'username' => $validated['username'],
                    'groupname' => $package->name,
                    'priority' => 1,
                ]);
            }
        });

        $customer = Customer::where('username', $validated['username'])->first();
        if ($customer && $customer->olt_id && $customer->onu_index) {
            \App\Jobs\ProvisionOnuJob::dispatch($customer->id);
        }

        return redirect()->route('customers.index')->with('success', 'Subscriber created successfully. OLT Provisioning has been queued.');
    }

    public function edit(Customer $customer)
    {
        $packages = Package::where('is_active', true)->get();
        $regions = \App\Models\Region::all();
        $stos = \App\Models\Sto::all();
        $stbs = \App\Models\Stb::all();
        
        $radCheck = \App\Models\Radius\RadCheck::where('username', $customer->username)
            ->where('attribute', 'Cleartext-Password')
            ->first();
        $customer->password = $radCheck ? $radCheck->value : ($customer->password ?? '');
        $olts = \App\Models\Olt::where('is_active', true)->get();
        $odcs = \App\Models\Odc::all();
        $odps = \App\Models\Odp::all();
        $partners = User::where('role', User::ROLE_MITRA)->get();
        $sales = User::where('role', User::ROLE_SALES)->orWhere('is_sales', true)->get();

        return view('customers.edit', compact('customer', 'packages', 'regions', 'stos', 'stbs', 'olts', 'odcs', 'odps', 'partners', 'sales'));
    }

    public function update(Request $request, Customer $customer)
    {
        // Pre-sanitize coordinates before validation
        if ($request->has('latitude')) {
            $request->merge(['latitude' => str_replace(',', '.', $request->input('latitude'))]);
        }
        if ($request->has('longitude')) {
            $request->merge(['longitude' => str_replace(',', '.', $request->input('longitude'))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:customers,username,' . $customer->id,
            'region_code' => 'required|string|size:3|exists:regions,code',
            'sto_code' => 'required|string|size:3|exists:stos,code',
            'stb_code' => 'required|string|size:3|exists:stbs,code',
            'email' => 'nullable|email|max:255',
            'ktp' => 'nullable|string|max:20|unique:customers,ktp,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'is_active' => 'nullable',
            'password' => 'required|string|min:4',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
            'customer_type' => 'required|in:personal,corporate,vip',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'installation_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'partner_id' => 'nullable|exists:users,id',
            'commission_rate' => 'nullable|numeric|min:0',
            'commission_type' => 'nullable|in:percentage,fixed',
            'sales_id' => 'nullable|exists:users,id',
            'sales_commission_rate' => 'nullable|numeric|min:0',
            'sales_commission_type' => 'nullable|in:percentage,fixed',
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string|max:64',
            'onu_index' => 'nullable|string|max:64',
            'onu_type' => 'nullable|string|max:64',
            'odc_id' => 'nullable|string|exists:odcs,id',
            'odp_id' => 'nullable|string|exists:odps,id',
            'odp_port' => 'nullable|integer',
            'cable_length' => 'nullable|integer',
            'vlan_id' => 'nullable|integer',
            'static_ip' => 'nullable|string',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'cpe_brand' => 'nullable|string',
            'cpe_model' => 'nullable|string',
            'cpe_mac' => 'nullable|string',
        ]);

        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;
        
        $oldUsername = $customer->username;
        $oldStatus = $customer->is_active;
        $newUsername = $validated['username'];
        $package = \App\Models\Package::find($validated['package_id']);
        $isActive = $request->has('is_active') ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) : $customer->is_active;
        
        DB::transaction(function () use ($customer, $validated, $oldUsername, $newUsername, $package, $isActive, $latitude, $longitude, $request) {
            // 1. Update customer fields (excluding coordinates)
            $updateData = collect($validated)->except(['latitude', 'longitude', 'identity_photo', 'house_photo', 'cpe_photo'])->toArray();
            $updateData['password'] = $validated['password'];
            $updateData['is_active'] = $isActive;
            $updateData['status'] = $isActive ? Customer::STATUS_ACTIVE : Customer::STATUS_SUSPENDED;
            $updateData['latitude'] = $latitude;
            $updateData['longitude'] = $longitude;
            $updateData['partner_id'] = $validated['partner_id'] ?? null;
            $updateData['commission_rate'] = $validated['commission_rate'] ?? null;
            $updateData['commission_type'] = $validated['commission_type'] ?? null;
            $updateData['sales_id'] = $validated['sales_id'] ?? null;
            $updateData['sales_commission_rate'] = $validated['sales_commission_rate'] ?? null;
            $updateData['sales_commission_type'] = $validated['sales_commission_type'] ?? null;
            $updateData['discount_type'] = $validated['discount_type'] ?? null;
            $updateData['discount_value'] = $validated['discount_value'] ?? 0;
            $updateData['use_tax'] = $request->has('use_tax');

            // Handle File Uploads
            foreach(['identity_photo', 'house_photo', 'cpe_photo'] as $field) {
                if ($request->hasFile($field)) {
                    // Delete old file if exists
                    if ($customer->$field) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($customer->$field);
                    }
                    $updateData[$field] = $request->file($field)->store('customers/kyc', 'public');
                }
            }

            $customer->update($updateData);

            // 2. Sync radcheck (Update username & password)
            \App\Models\Radius\RadCheck::updateOrCreate(
                ['username' => $oldUsername, 'attribute' => 'Cleartext-Password'],
                [
                    'username' => $newUsername,
                    'value' => $validated['password'],
                    'op' => ':='
                ]
            );

            // 3. Sync radusergroup
            \App\Models\Radius\RadUserGroup::where('username', $oldUsername)->update([
                'username' => $newUsername,
                'groupname' => $package->name
            ]);

            // 4. Sync radacct history (agar riwayat tetap terhubung)
            DB::table('radacct')->where('username', $oldUsername)->update([
                'username' => $newUsername
            ]);
        });

        // 5. Trigger RADIUS CoA Disconnect if suspended or package changed
        $isPackageChanged = $customer->wasChanged('package_id');
        
        if (($oldStatus && !$isActive) || $isPackageChanged || ($oldUsername !== $newUsername)) {
            try {
                $nas = Nas::first(); 
                if ($nas) {
                    $coaService = new \App\Services\RadiusCoAService();
                    $coaService->disconnect($nas->nasipaddress, $nas->secret, $newUsername);
                }
            } catch (\Exception $e) {
                \Log::warning("CoA Disconnect failed: " . $e->getMessage());
            }
        }

        // 6. Trigger OLT Jobs if OLT is configured
        if ($customer->olt_id && $customer->onu_index) {
            // Check status change
            if ($oldStatus && !$isActive) {
                \App\Jobs\UpdateOnuJob::dispatch($customer->olt_id, $customer->onu_index, 'suspend');
            } elseif (!$oldStatus && $isActive) {
                \App\Jobs\UpdateOnuJob::dispatch($customer->olt_id, $customer->onu_index, 'resume');
            }

            // Check package change for bandwidth update
            if ($isPackageChanged) {
                $bandwidth = $package->speed_limit_down ?? 102400;
                \App\Jobs\UpdateOnuJob::dispatch($customer->olt_id, $customer->onu_index, 'update_speed', $bandwidth);
            }
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Subscriber and RADIUS records updated successfully. OLT changes queued.');
    }

    public function destroy(Customer $customer)
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized: Only administrators can delete subscriber records.');

        // Backup OLT info for deprovisioning job
        $oltId = $customer->olt_id;
        $onuIndex = $customer->onu_index;
        $onuSn = $customer->onu_sn;

        DB::transaction(function () use ($customer) {
            // Delete RADIUS records first to prevent orphaned records if delete fails
            RadCheck::where('username', $customer->username)->delete();
            RadUserGroup::where('username', $customer->username)->delete();
            
            // Delete Billing Customer
            $customer->delete();
        });

        // 2. Trigger RADIUS CoA Disconnect to kick active sessions
        try {
            $nas = Nas::first(); 
            if ($nas) {
                $coaService = new \App\Services\RadiusCoAService();
                $coaService->disconnect($nas->nasipaddress, $nas->secret, $customer->username);
            }
        } catch (\Exception $e) {
            \Log::warning("CoA Disconnect failed during customer deletion: " . $e->getMessage());
        }

        // 3. Trigger OLT Deprovisioning
        if ($oltId && $onuIndex) {
            \App\Jobs\DeprovisionOnuJob::dispatch($oltId, $onuIndex, $onuSn);
        }

        return redirect()->route('customers.index')->with('success', 'Subscriber deleted successfully.');
    }

    public function resetFup(Customer $customer)
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $customer->update([
            'current_month_usage_gb' => 0,
            'last_usage_sync' => now()
        ]);

        // Restore normal speed in RADIUS
        $package = $customer->package;
        if ($package) {
            $normalSpeed = $package->mikrotik_rate_limit;
            \App\Models\Radius\RadReply::updateOrCreate(
                ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => '=', 'value' => $normalSpeed]
            );

            // Trigger Disconnect so they get normal speed back
            try {
                $nas = \App\Models\Radius\Nas::first();
                if ($nas) {
                    $coaService = new \App\Services\RadiusCoAService();
                    $coaService->disconnect($nas->nasipaddress, $nas->secret, $customer->username);
                }
            } catch (\Exception $e) {
                \Log::warning("CoA Disconnect failed: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'FUP usage has been reset and normal speed restored.');
    }
}
