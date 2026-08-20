<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Sto;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\PartnerCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SystemAdminController extends Controller
{
    /**
     * List all staff users.
     */
    public function users(Request $request)
    {
        $users = User::latest()->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'phone' => $u->phone,
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at ? $u->created_at->format('Y-m-d') : null,
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Create new staff user.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:administrator,admin,teknisi,finance,kasir,customer_service',
            'phone' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => "Pengguna {$user->name} berhasil ditambahkan.",
            'user_id' => $user->id,
        ], 201);
    }

    /**
     * Toggle active status of a user.
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message' => "Akun {$user->name} berhasil {$statusText}.",
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Get system settings.
     */
    public function settings()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return response()->json([
            'settings' => [
                'company_name' => $settings['company_name'] ?? 'ISP Billing Provider',
                'company_phone' => $settings['company_phone'] ?? '081234567890',
                'company_address' => $settings['company_address'] ?? 'Indonesia',
                'tax_percentage' => (float) ($settings['tax_percentage'] ?? 11),
                'wa_gateway_enabled' => ($settings['wa_gateway_enabled'] ?? '1') === '1',
                'radius_auto_coa' => ($settings['radius_auto_coa'] ?? '1') === '1',
            ]
        ]);
    }

    /**
     * Update system settings.
     */
    public function updateSettings(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        return response()->json([
            'message' => 'Pengaturan sistem berhasil disimpan.',
        ]);
    }

    /**
     * Get network locations summary (STOs, ODCs, ODPs).
     */
    public function networkLocations()
    {
        $stos = Sto::select('id', 'name', 'code', 'address')->get();
        $odcs = Odc::select('id', 'name', 'code', 'total_ports')->get();
        $odps = Odp::select('id', 'name', 'code', 'total_ports')->get();

        return response()->json([
            'stats' => [
                'total_stos' => $stos->count(),
                'total_odcs' => $odcs->count(),
                'total_odps' => $odps->count(),
            ],
            'stos' => $stos,
            'odcs' => $odcs,
            'odps' => $odps,
        ]);
    }
}
