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
            'mikrotik_group' => 'nullable|string|max:255',
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
            'enable_fup' => 'nullable|boolean',
            'fup_limit_gb' => 'nullable|integer|min:0',
            'fup_speed_limit' => 'nullable|string',
        ]);

        $validated['enable_fup'] = $request->has('enable_fup');

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

            if ($package->mikrotik_group) {
                RadGroupReply::create([
                    'groupname' => $package->name,
                    'attribute' => 'Mikrotik-Group',
                    'op' => '=',
                    'value' => $package->mikrotik_group,
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
            'mikrotik_group' => 'nullable|string|max:255',
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
            'enable_fup' => 'nullable|boolean',
            'fup_limit_gb' => 'nullable|integer|min:0',
            'fup_speed_limit' => 'nullable|string',
        ]);

        $validated['enable_fup'] = $request->has('enable_fup');

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

            if ($package->mikrotik_group) {
                RadGroupReply::updateOrCreate(
                    ['groupname' => $package->name, 'attribute' => 'Mikrotik-Group'],
                    ['op' => '=', 'value' => $package->mikrotik_group]
                );
            } else {
                RadGroupReply::where('groupname', $package->name)->where('attribute', 'Mikrotik-Group')->delete();
            }
        });

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        // Cegah hapus jika paket masih digunakan oleh pelanggan
        $customerCount = $package->customers()->count();
        if ($customerCount > 0) {
            return redirect()->route('packages.index')->with(
                'error',
                "Paket '{$package->name}' tidak dapat dihapus karena masih digunakan oleh {$customerCount} pelanggan aktif. Silakan alihkan atau hapus pelanggan tersebut terlebih dahulu."
            );
        }

        try {
            DB::transaction(function () use ($package) {
                RadGroupReply::where('groupname', $package->name)->delete();
                $package->delete();
            });

            return redirect()->route('packages.index')->with('success', "Paket '{$package->name}' berhasil dihapus.");
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('packages.index')->with(
                'error',
                "Gagal menghapus paket '{$package->name}' karena data ini masih terhubung dengan data lain pada sistem."
            );
        } catch (\Exception $e) {
            return redirect()->route('packages.index')->with(
                'error',
                "Terjadi kendala saat menghapus paket: " . $e->getMessage()
            );
        }
    }
}
