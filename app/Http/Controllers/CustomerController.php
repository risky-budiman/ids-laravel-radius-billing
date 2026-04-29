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
    public function index()
    {
        $query = Customer::with('package');
        
        if (auth()->user()->isMitra()) {
            $query->where('partner_id', auth()->id());
        }

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

        return view('customers.index', compact('customers'));
    }

    public function map()
    {
        // Simple query: just get those that have something in latitude
        $query = Customer::where('latitude', '!=', '')
            ->whereNotNull('latitude')
            ->with('package');

        if (auth()->user()->isMitra()) {
            $query->where('partner_id', auth()->id());
        }

        $customers = $query->get();
            
        return view('customers.map', compact('customers'));
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

    public function create()
    {
        $packages = Package::where('is_active', true)->get();
        $regions = \App\Models\Region::all();
        $stos = \App\Models\Sto::all();
        $stbs = \App\Models\Stb::all();
        $olts = \App\Models\Olt::where('is_active', true)->get();
        $odcs = \App\Models\Odc::all();
        $odps = \App\Models\Odp::all();
        $partners = User::where('role', User::ROLE_MITRA)->get();
        $sales = User::where('role', User::ROLE_SALES)->get();
        return view('customers.create', compact('packages', 'regions', 'stos', 'stbs', 'olts', 'odcs', 'odps', 'partners', 'sales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:64|unique:customers,username|unique:radcheck,username',
            'password' => 'required|string|min:6',
            'name' => 'required|string|max:255',
            'region_code' => 'required|string|size:3|exists:regions,code',
            'sto_code' => 'required|string|size:3|exists:stos,code',
            'stb_code' => 'required|string|size:3|exists:stbs,code',
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
        ]);

        $package = Package::find($validated['package_id']);
        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);

        DB::transaction(function () use ($validated, $package, $isActive, $request) {
            // Handle File Uploads
            $photos = [];
            foreach(['identity_photo', 'house_photo', 'cpe_photo'] as $field) {
                if ($request->hasFile($field)) {
                    $photos[$field] = $request->file($field)->store('customers/kyc', 'public');
                }
            }

            // Create in Billing
            Customer::create(array_merge([
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
                'is_active' => $isActive,
                'status' => $isActive ? Customer::STATUS_ACTIVE : Customer::STATUS_NEW,
                'billing_type' => $validated['billing_type'],
                'billing_method' => $validated['billing_method'],
                'billing_day' => $validated['billing_day'] ?? 1,
                'billing_due_day' => $validated['billing_due_day'] ?? 20,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
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
            \App\Jobs\ProvisionOnuJob::dispatch($customer);
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
        $sales = User::where('role', User::ROLE_SALES)->get();

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

        // Trigger OLT Deprovisioning
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
