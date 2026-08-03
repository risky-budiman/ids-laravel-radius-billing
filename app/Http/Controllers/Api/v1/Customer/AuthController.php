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
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('username', $request->username)->first();

        $isPasswordCorrect = false;
        if ($customer) {
            try {
                if (Hash::check($request->password, $customer->password)) {
                    $isPasswordCorrect = true;
                }
            } catch (\Throwable $e) {
                // Ignore exception if password is not a valid hash, and proceed to plaintext check
            }

            if (!$isPasswordCorrect && $request->password === $customer->password) {
                $isPasswordCorrect = true;
            }
        }

        if (!$customer || !$isPasswordCorrect) {
            throw ValidationException::withMessages([
                'username' => ['Kredensial yang diberikan salah.'],
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
