<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
                'package_speed' => $customer->package ? $customer->package->speed : null,
            ]
        ]);
    }
}
