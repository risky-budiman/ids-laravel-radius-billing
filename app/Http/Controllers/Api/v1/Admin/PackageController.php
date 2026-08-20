<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Radius\RadGroupReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    /**
     * List all internet packages with customer count.
     */
    public function index()
    {
        $packages = Package::withCount('customers')->get()->map(function ($p) {
            return [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'price' => (float) $p->price,
                'download_speed' => $p->download_speed,
                'upload_speed' => $p->upload_speed,
                'is_active' => (bool) $p->is_active,
                'enable_fup' => (bool) $p->enable_fup,
                'fup_limit_gb' => $p->fup_limit_gb ? (float) $p->fup_limit_gb : null,
                'fup_speed_limit' => $p->fup_speed_limit,
                'priority' => (int) ($p->priority ?? 8),
                'description' => $p->description,
                'customers_count' => (int) ($p->customers_count ?? 0),
            ];
        });

        return response()->json([
            'packages' => $packages
        ]);
    }

    /**
     * Show single package detail.
     */
    public function show($id)
    {
        $package = Package::withCount('customers')->findOrFail($id);

        return response()->json([
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'mikrotik_group' => $package->mikrotik_group,
                'price' => (float) $package->price,
                'download_speed' => $package->download_speed,
                'upload_speed' => $package->upload_speed,
                'burst_limit_up' => $package->burst_limit_up,
                'burst_limit_down' => $package->burst_limit_down,
                'priority' => $package->priority,
                'is_active' => (bool) $package->is_active,
                'enable_fup' => (bool) $package->enable_fup,
                'fup_limit_gb' => (float) ($package->fup_limit_gb ?? 0),
                'fup_speed_limit' => $package->fup_speed_limit,
                'description' => $package->description,
                'customers_count' => $package->customers_count,
                'created_at' => $package->created_at ? $package->created_at->toIso8601String() : null,
            ]
        ]);
    }

    /**
     * Store new package.
     */
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
            'fup_limit_gb' => 'nullable|numeric|min:0',
            'fup_speed_limit' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $package = DB::transaction(function () use ($validated, $request) {
            $package = Package::create(array_merge($validated, [
                'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                'enable_fup' => filter_var($request->input('enable_fup', false), FILTER_VALIDATE_BOOLEAN),
            ]));

            $rateLimit = $package->mikrotik_rate_limit;
            if ($rateLimit) {
                RadGroupReply::updateOrCreate(
                    ['groupname' => $package->name, 'attribute' => 'Mikrotik-Rate-Limit'],
                    ['op' => '=', 'value' => $rateLimit]
                );
            }

            return $package;
        });

        return response()->json([
            'message' => 'Paket internet berhasil ditambahkan',
            'package_id' => $package->id,
        ], 201);
    }

    /**
     * Update package.
     */
    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);

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
            'fup_limit_gb' => 'nullable|numeric|min:0',
            'fup_speed_limit' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($package, $validated, $request) {
            $oldName = $package->name;
            $package->update(array_merge($validated, [
                'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                'enable_fup' => filter_var($request->input('enable_fup', false), FILTER_VALIDATE_BOOLEAN),
            ]));

            // Update RADIUS
            $rateLimit = $package->mikrotik_rate_limit;
            if ($rateLimit) {
                RadGroupReply::where('groupname', $oldName)->where('attribute', 'Mikrotik-Rate-Limit')->delete();
                RadGroupReply::create([
                    'groupname' => $package->name,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => '=',
                    'value' => $rateLimit,
                ]);
            }
        });

        return response()->json([
            'message' => 'Paket internet berhasil diperbarui',
        ]);
    }

    /**
     * Delete package.
     */
    public function destroy($id)
    {
        $package = Package::findOrFail($id);

        if ($package->customers()->count() > 0) {
            return response()->json([
                'message' => 'Paket tidak dapat dihapus karena masih digunakan oleh pelanggan.',
            ], 422);
        }

        DB::transaction(function () use ($package) {
            RadGroupReply::where('groupname', $package->name)->delete();
            $package->delete();
        });

        return response()->json([
            'message' => 'Paket berhasil dihapus',
        ]);
    }
}
