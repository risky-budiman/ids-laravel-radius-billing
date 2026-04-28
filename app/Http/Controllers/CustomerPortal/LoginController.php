<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('customer-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'phone' => 'required',
        ]);

        $customer_code = $request->customer_id;
        $phone = $request->phone;

        // Find customer by code and phone (initial verification)
        $customer = Customer::where('customer_code', $customer_code)
            ->where('phone', 'like', '%' . substr($phone, -10)) // Flexible match for phone
            ->first();

        if (!$customer) {
            return back()->with('error', 'Data pelanggan tidak ditemukan atau nomor HP tidak cocok.');
        }

        if (!$customer->is_active) {
            return back()->with('error', 'Layanan Anda sedang non-aktif. Silakan hubungi Admin.');
        }

        // Zero-Touch: If customer has no password, set it to their phone number
        if (!$customer->password) {
            $customer->update([
                'password' => Hash::make($phone)
            ]);
        }

        // Log in the customer directly using the 'customer' guard
        Auth::guard('customer')->login($customer, $request->boolean('remember'));

        $request->session()->regenerate();
        return redirect()->route('customer.dashboard')->with('success', 'Selamat datang di Portal Pelanggan!');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
