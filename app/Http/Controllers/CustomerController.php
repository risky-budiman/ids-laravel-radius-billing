<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use App\Models\Nas;
use App\Services\RadiusCoAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('package')->paginate(10);
        
        // Fetch passwords for these customers from radcheck
        $usernames = $customers->pluck('username')->toArray();
        $passwords = \App\Models\Radius\RadCheck::whereIn('username', $usernames)
            ->where('attribute', 'Cleartext-Password')
            ->get()
            ->pluck('value', 'username');

        foreach ($customers as $customer) {
            $customer->password = $passwords[$customer->username] ?? '-';
        }

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
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
        return view('customers.create', compact('packages', 'regions', 'stos', 'stbs'));
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
            'is_active' => 'boolean',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
        ]);

        $package = Package::find($validated['package_id']);

        DB::transaction(function () use ($validated, $package) {
            // Create in Billing
            Customer::create([
                'customer_code' => $validated['customer_code'],
                'region_code' => $validated['region_code'] ?? '000',
                'sto_code' => $validated['sto_code'] ?? '000',
                'stb_code' => $validated['stb_code'] ?? '000',
                'username' => $validated['username'],
                'name' => $validated['name'],
                'ktp' => $validated['ktp'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'package_id' => $validated['package_id'],
                'is_active' => false,
                'status' => Customer::STATUS_NEW,
                'billing_type' => $validated['billing_type'],
                'billing_method' => $validated['billing_method'],
                'billing_day' => $validated['billing_day'] ?? 1,
                'billing_due_day' => $validated['billing_due_day'] ?? 20,
            ]);

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

        return redirect()->route('customers.index')->with('success', 'Subscriber created successfully and synced to RADIUS.');
    }

    public function edit(Customer $customer)
    {
        $packages = Package::where('is_active', true)->get();
        $regions = \App\Models\Region::all();
        $stos = \App\Models\Sto::all();
        $stbs = \App\Models\Stb::all();
        
        $radCheck = \App\Models\Radius\RadCheck::where('username', $customer->username)->first();
        $customer->password = $radCheck ? $radCheck->value : '';

        return view('customers.edit', compact('customer', 'packages', 'regions', 'stos', 'stbs'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:customers,username,' . $customer->id,
            'region_code' => 'required|string|size:3|exists:regions,code',
            'sto_code' => 'required|string|size:3|exists:stos,code',
            'stb_code' => 'required|string|size:3|exists:stbs,code',
            'email' => 'nullable|email|max:255',
            'ktp' => 'nullable|string|max:20|unique:customers,ktp,' . $customer->id,
            'customer_code' => 'required|string|max:20|unique:customers,customer_code,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'is_active' => 'boolean',
            'password' => 'required|string|min:4',
            'billing_type' => 'required|in:prepaid,postpaid',
            'billing_method' => 'required|in:cycle,fixed,renewal',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'billing_due_day' => 'nullable|integer|min:1|max:28',
        ]);

        $oldUsername = $customer->username;
        $newUsername = $validated['username'];
        $package = \App\Models\Package::find($validated['package_id']);

        \DB::transaction(function () use ($customer, $validated, $oldUsername, $newUsername, $package) {
            // 1. Update Customer Table
            $customer->update($validated);

            // 2. Sync radcheck (Update username & password)
            \App\Models\Radius\RadCheck::where('username', $oldUsername)->update([
                'username' => $newUsername,
                'value' => $validated['password']
            ]);

            // 3. Sync radusergroup
            \App\Models\Radius\RadUserGroup::where('username', $oldUsername)->update([
                'username' => $newUsername,
                'groupname' => $package->name
            ]);

            // 4. Sync radacct history (agar riwayat tetap terhubung)
            \DB::table('radacct')->where('username', $oldUsername)->update([
                'username' => $newUsername
            ]);
        });

        // 5. Trigger RADIUS CoA Disconnect if suspended or package changed
        $newStatus = $validated['is_active'] ?? true;
        $isPackageChanged = $customer->wasChanged('package_id');
        
        if (($oldStatus && !$newStatus) || $isPackageChanged || ($oldUsername !== $newUsername)) {
            $nas = \App\Models\Nas::first(); 
            if ($nas) {
                $coaService = new \App\Services\RadiusCoAService();
                $coaService->disconnect($nas->nasipaddress, $nas->secret, $newUsername);
            }
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Subscriber and RADIUS records updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized: Only administrators can delete subscriber records.');

        DB::transaction(function () use ($customer) {
            // Delete RADIUS records first to prevent orphaned records if delete fails
            RadCheck::where('username', $customer->username)->delete();
            RadUserGroup::where('username', $customer->username)->delete();
            
            // Delete Billing Customer
            $customer->delete();
        });

        return redirect()->route('customers.index')->with('success', 'Subscriber deleted successfully.');
    }
}
