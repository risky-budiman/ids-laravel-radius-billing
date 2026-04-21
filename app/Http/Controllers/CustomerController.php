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
        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load('package');
        return view('customers.show', compact('customer'));
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
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'is_active' => 'boolean',
        ]);

        $package = Package::find($validated['package_id']);

        DB::transaction(function () use ($validated, $package) {
            // Create in Billing
            Customer::create([
                'region_code' => $validated['region_code'] ?? '000',
                'sto_code' => $validated['sto_code'] ?? '000',
                'stb_code' => $validated['stb_code'] ?? '000',
                'username' => $validated['username'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'package_id' => $validated['package_id'],
                'is_active' => false,
                'status' => Customer::STATUS_NEW,
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
        return view('customers.edit', compact('customer', 'packages', 'regions', 'stos', 'stbs'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_code' => 'required|string|size:3|exists:regions,code',
            'sto_code' => 'required|string|size:3|exists:stos,code',
            'stb_code' => 'required|string|size:3|exists:stbs,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'password' => 'nullable|string|min:6',
            'is_active' => 'boolean',
        ]);

        $package = Package::find($validated['package_id']);
        $oldPackageId = $customer->package_id;
        $isPackageChanged = $oldPackageId != $validated['package_id'];
        $oldStatus = $customer->is_active;

        DB::transaction(function () use ($validated, $customer, $package, $isPackageChanged) {
            $customer->update([
                'name' => $validated['name'],
                'region_code' => $validated['region_code'] ?? $customer->region_code,
                'sto_code' => $validated['sto_code'] ?? $customer->sto_code,
                'stb_code' => $validated['stb_code'] ?? $customer->stb_code,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'package_id' => $validated['package_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Update RADIUS Password if changed
            if (!empty($validated['password'])) {
                RadCheck::where('username', $customer->username)
                    ->where('attribute', 'Cleartext-Password')
                    ->update(['value' => $validated['password']]);
            }

            // Update RADIUS Group (Package) if changed
            if ($isPackageChanged && $package) {
                RadUserGroup::where('username', $customer->username)->delete();
                RadUserGroup::create([
                    'username' => $customer->username,
                    'groupname' => $package->name,
                    'priority' => 1,
                ]);
            }
        });

        // Trigger RADIUS CoA Disconnect if suspended or package changed
        $newStatus = $validated['is_active'] ?? true;
        if (($oldStatus && !$newStatus) || $isPackageChanged) {
            $nas = Nas::first(); 
            if ($nas) {
                $coaService = new RadiusCoAService();
                $coaService->disconnect($nas->ip_address, $nas->secret, $customer->username);
            }
        }

        return redirect()->route('customers.index')->with('success', 'Subscriber updated successfully.');
    }

    public function destroy(Customer $customer)
    {
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
