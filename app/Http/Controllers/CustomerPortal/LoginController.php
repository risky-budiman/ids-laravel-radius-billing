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

        $customer_code = trim($request->customer_id);
        $phone = trim($request->phone);

        $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);
        $phoneSuffix = strlen($cleanedPhone) >= 9 ? substr($cleanedPhone, -9) : $cleanedPhone;

        // Find customer by code, username, or ID and phone
        $customer = Customer::where(function ($q) use ($customer_code) {
                $q->where('customer_code', $customer_code)
                  ->orWhere('username', $customer_code)
                  ->orWhere('username', 'like', $customer_code . '@%')
                  ->orWhere('id', $customer_code);
            })
            ->where(function ($q) use ($phone, $phoneSuffix) {
                $q->where('phone', 'like', '%' . $phoneSuffix)
                  ->orWhere('phone', $phone);
            })
            ->first();

        if (!$customer) {
            return back()->with('error', 'Data pelanggan tidak ditemukan atau nomor HP tidak cocok.');
        }

        if ($customer->status === Customer::STATUS_TERMINATED) {
            return back()->with('error', 'Akun pelanggan ini telah berhenti berlangganan. Silakan hubungi Admin.');
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
