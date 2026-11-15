<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Ubah data kontak pelanggan (HP, email, alamat) — dipakai CS. Paket, harga, status & akun
 * PPPoE tidak bisa diubah lewat sini (ganti paket lewat pengajuan).
 */
class CustomerContactController extends Controller
{
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]*$/',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ], [], ['phone' => 'nomor HP', 'address' => 'alamat']);

        $customer->update($data);   // tercatat di Log Aktivitas (LogsActivity)

        return back()->with('success', 'Data kontak pelanggan diperbarui.');
    }
}
