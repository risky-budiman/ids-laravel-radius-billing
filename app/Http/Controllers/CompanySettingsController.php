<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Invoice & Portal (administrator): format cetak & catatan kaki invoice, login OTP portal.
 * Identitas perusahaan (nama, kontak, logo) ada di Pengaturan Aplikasi — dulu diedit di dua halaman.
 */
class CompanySettingsController extends Controller
{
    public function index()
    {
        return view('settings.company');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'invoice_format' => 'required|in:A4,Thermal',
            'invoice_footer_note' => 'nullable|string|max:1000',
            'portal_login_otp' => 'nullable|boolean',
        ], [], ['invoice_format' => 'format cetak', 'invoice_footer_note' => 'catatan kaki']);

        $values = [
            'invoice_format' => $data['invoice_format'],
            'invoice_footer_note' => (string) ($data['invoice_footer_note'] ?? ''),
            'portal_login_otp' => $request->boolean('portal_login_otp') ? '1' : '0',
        ];
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string']);
        }
        Cache::forget('app_settings');

        return redirect()->route('settings.company')->with('success', 'Pengaturan invoice & portal disimpan.');
    }
}
