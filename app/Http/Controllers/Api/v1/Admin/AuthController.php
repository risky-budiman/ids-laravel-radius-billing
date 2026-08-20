<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login admin/staff user and return Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Only allow staff roles (not customer)
        $allowedRoles = [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_ADMIN,
            User::ROLE_TEKNISI,
            User::ROLE_KASIR,
            User::ROLE_SALES,
            User::ROLE_MITRA,
        ];

        if (!in_array($user->role, $allowedRoles)) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak memiliki akses ke Admin Portal.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah dinonaktifkan. Hubungi administrator.'],
            ]);
        }

        // Revoke old admin tokens for this device
        $user->tokens()->where('name', 'admin-portal-token')->delete();

        // Generate new Sanctum token
        $token = $user->createToken('admin-portal-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_sales' => (bool) $user->is_sales,
                'is_active' => (bool) $user->is_active,
                'avatar_url' => $user->avatar_url,
            ]
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated admin profile.
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_sales' => (bool) $user->is_sales,
                'is_active' => (bool) $user->is_active,
                'avatar_url' => $user->avatar_url,
                'profile_photo' => $user->profile_photo,
                'commission_rate' => $user->commission_rate,
                'commission_type' => $user->commission_type,
                'bank_name' => $user->bank_name,
                'bank_account_number' => $user->bank_account_number,
                'bank_account_name' => $user->bank_account_name,
                'created_at' => $user->created_at->toIso8601String(),
            ]
        ]);
    }
}
