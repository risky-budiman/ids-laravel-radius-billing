<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Radius\RadGroupReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::paginate(10);
        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'price' => 'required|numeric|min:0',
            'download_speed' => 'nullable|string',
            'upload_speed' => 'nullable|string',
            'burst_limit_up' => 'nullable|string',
            'burst_limit_down' => 'nullable|string',
            'burst_threshold_up' => 'nullable|string',
            'burst_threshold_down' => 'nullable|string',
            'burst_time_up' => 'nullable|string',
            'burst_time_down' => 'nullable|string',
            'limit_at_up' => 'nullable|string',
            'limit_at_down' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:8',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $package = Package::create($validated);

            $rateLimit = $package->mikrotik_rate_limit;
            if ($rateLimit) {
                RadGroupReply::create([
                    'groupname' => $package->name,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => '=',
                    'value' => $rateLimit,
                ]);
            }
        });

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name,' . $package->id,
            'price' => 'required|numeric|min:0',
            'download_speed' => 'nullable|string',
            'upload_speed' => 'nullable|string',
            'burst_limit_up' => 'nullable|string',
            'burst_limit_down' => 'nullable|string',
            'burst_threshold_up' => 'nullable|string',
            'burst_threshold_down' => 'nullable|string',
            'burst_time_up' => 'nullable|string',
            'burst_time_down' => 'nullable|string',
            'limit_at_up' => 'nullable|string',
            'limit_at_down' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:8',
            'description' => 'nullable|string',
        ]);

        $oldName = $package->name;

        DB::transaction(function () use ($validated, $package, $oldName) {
            $package->update($validated);

            // Update RADIUS
            if ($oldName !== $validated['name']) {
                RadGroupReply::where('groupname', $oldName)->update(['groupname' => $validated['name']]);
            }

            $rateLimit = $package->mikrotik_rate_limit;
            if ($rateLimit) {
                RadGroupReply::updateOrCreate(
                    ['groupname' => $package->name, 'attribute' => 'Mikrotik-Rate-Limit'],
                    ['op' => '=', 'value' => $rateLimit]
                );
            } else {
                RadGroupReply::where('groupname', $package->name)->where('attribute', 'Mikrotik-Rate-Limit')->delete();
            }
        });

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        DB::transaction(function () use ($package) {
            RadGroupReply::where('groupname', $package->name)->delete();
            $package->delete();
        });

        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }
}
