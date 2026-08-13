<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Radius\RadAcct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'customer_code' => 'required|string',
            'phone' => 'required|string',
        ]);

        $customer = Customer::where('customer_code', $request->customer_code)
            ->where('phone', $request->phone)
            ->first();

        if (!$customer) {
            throw ValidationException::withMessages([
                'customer_code' => ['ID Pelanggan atau Nomor HP terdaftar salah.'],
            ]);
        }

        // Generate Sanctum Token
        $token = $customer->createToken('customer-mobile-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'username' => $customer->username,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'is_active' => $customer->is_active,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    public function profile(Request $request)
    {
        $customer = $request->user();

        // Query active session from radacct
        $activeSession = RadAcct::where('username', $customer->username)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        $sessionData = null;
        if ($activeSession) {
            // Calculate uptime
            $sessionStart = $activeSession->acctstarttime;
            $uptimeSeconds = $activeSession->acctsessiontime;
            if ($sessionStart) {
                $uptimeSeconds = now()->diffInSeconds($sessionStart);
            }

            // Convert octets to MB
            $uploadMb = round(($activeSession->acctinputoctets ?? 0) / 1048576, 2);
            $downloadMb = round(($activeSession->acctoutputoctets ?? 0) / 1048576, 2);

            $sessionData = [
                'ip_address' => $activeSession->framedipaddress,
                'mac_address' => $activeSession->callingstationid,
                'uptime_seconds' => $uptimeSeconds,
                'upload_mb' => $uploadMb,
                'download_mb' => $downloadMb,
                'session_start' => $sessionStart ? $sessionStart->toIso8601String() : null,
            ];
        }

        // Booster Info
        $startOfMonth = now()->startOfMonth();
        $totalBoosterQuotaGb = \App\Models\CustomerBooster::where('customer_id', $customer->id)
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)
            ->join('boosters', 'customer_boosters.booster_id', '=', 'boosters.id')
            ->sum('boosters.quota_gb') ?: 0;

        $limitGb = $customer->package ? $customer->package->fup_limit_gb : 0;
        $usageGb = (float)($customer->current_month_usage_gb ?? 0);
        $totalLimitGb = $limitGb + $totalBoosterQuotaGb;

        if ($totalBoosterQuotaGb > 0) {
            if ($usageGb < $limitGb) {
                $remainingBoosterQuotaGb = $totalBoosterQuotaGb;
            } else {
                $remainingBoosterQuotaGb = max(0, $totalLimitGb - $usageGb);
            }
        } else {
            $remainingBoosterQuotaGb = 0;
        }

        $activeBooster = \App\Models\CustomerBooster::where('customer_id', $customer->id)
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)
            ->with('booster')
            ->orderBy('paid_at', 'desc')
            ->first();

        $hasActiveBooster = ($totalBoosterQuotaGb > 0 && $remainingBoosterQuotaGb > 0);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'username' => $customer->username,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'status' => $customer->status,
                'is_active' => $customer->is_active,
                'type' => $customer->type,
                'package_name' => $customer->package ? $customer->package->name : null,
                'package_price' => $customer->package ? $customer->package->price : null,
                'package_speed' => $customer->package ? $customer->package->download_speed : null,
                'current_month_usage_gb' => (float)($customer->current_month_usage_gb ?? 0),
                'last_usage_sync' => $customer->last_usage_sync ? $customer->last_usage_sync->toIso8601String() : null,
                'enable_fup' => $customer->package ? (bool)$customer->package->enable_fup : false,
                'fup_limit_gb' => $customer->package ? (float)$customer->package->fup_limit_gb : 0,
                'fup_speed_limit' => $customer->package ? $customer->package->fup_speed_limit : null,
                'is_online' => (bool)$activeSession,
                'active_session' => $sessionData,
                'has_active_booster' => $hasActiveBooster,
                'booster_name' => $activeBooster ? $activeBooster->booster->name : null,
                'booster_quota_gb' => (float)$totalBoosterQuotaGb,
                'remaining_booster_quota_gb' => (float)$remainingBoosterQuotaGb,
                'booster_speed' => $customer->package ? $customer->package->download_speed : null,
            ]
        ]);
    }

    public function savePushToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $request->user()->update([
            'expo_push_token' => $request->token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expo push token saved successfully.'
        ]);
    }
}
