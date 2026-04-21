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
            'type' => 'required|in:pppoe,hotspot',
            'price' => 'required|numeric|min:0',
            'download_speed' => 'nullable|integer|min:1',
            'upload_speed' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $package = Package::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'price' => $validated['price'],
                'download_speed' => $validated['download_speed'] ?? null,
                'upload_speed' => $validated['upload_speed'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['download_speed']) && !empty($validated['upload_speed'])) {
                $rateLimit = $validated['upload_speed'] . 'M/' . $validated['download_speed'] . 'M';
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
            'type' => 'required|in:pppoe,hotspot',
            'price' => 'required|numeric|min:0',
            'download_speed' => 'nullable|integer|min:1',
            'upload_speed' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldName = $package->name;

        DB::transaction(function () use ($validated, $package, $oldName) {
            $package->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'price' => $validated['price'],
                'download_speed' => $validated['download_speed'] ?? null,
                'upload_speed' => $validated['upload_speed'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Update RADIUS
            if ($oldName !== $validated['name']) {
                RadGroupReply::where('groupname', $oldName)->update(['groupname' => $validated['name']]);
                // We should also update radusergroup but keeping it simple for now
            }

            if (!empty($validated['download_speed']) && !empty($validated['upload_speed'])) {
                $rateLimit = $validated['upload_speed'] . 'M/' . $validated['download_speed'] . 'M';
                $reply = RadGroupReply::where('groupname', $validated['name'])->where('attribute', 'Mikrotik-Rate-Limit')->first();
                if ($reply) {
                    $reply->update(['value' => $rateLimit]);
                } else {
                    RadGroupReply::create([
                        'groupname' => $validated['name'],
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op' => '=',
                        'value' => $rateLimit,
                    ]);
                }
            } else {
                RadGroupReply::where('groupname', $validated['name'])->where('attribute', 'Mikrotik-Rate-Limit')->delete();
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
