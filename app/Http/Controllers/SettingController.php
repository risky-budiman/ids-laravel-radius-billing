<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan Aplikasi (administrator): identitas perusahaan, modul, grup WhatsApp tiket.
 *
 * Hanya kunci di RULES yang disimpan. Dulu semua isian permintaan disimpan sebagai pengaturan dan
 * rutenya terbuka untuk semua staf, sehingga kasir/teknisi/sales bisa mengubah pengaturan apa pun
 * (OTP portal, isolir, dismantle otomatis, …) dengan mengirim kunci tambahan.
 */
class SettingController extends Controller
{
    public const RULES = [
        'company_name' => 'required|string|max:150',
        'company_tax_id' => 'nullable|string|max:30',
        'company_email' => 'nullable|email|max:150',
        'company_phone' => 'nullable|string|max:20',
        'company_website' => 'nullable|url|max:200',
        'company_domain' => 'nullable|string|max:60|regex:/^[A-Za-z0-9.-]*$/',
        'company_address' => 'nullable|string|max:500',
        'enable_partner_module' => 'nullable|boolean',
        'default_commission_rate' => 'nullable|numeric|min:0',
        'commission_type' => 'nullable|in:percentage,fixed',
        'enable_sales_commission_module' => 'nullable|boolean',
        'default_sales_commission_rate' => 'nullable|numeric|min:0',
        'default_sales_commission_type' => 'nullable|in:percentage,fixed',
        'enable_fup_module' => 'nullable|boolean',
        'whatsapp_group_id' => 'nullable|string|max:80',
        'whatsapp_group_id_gangguan' => 'nullable|string|max:80',
        'whatsapp_group_id_aktivasi' => 'nullable|string|max:80',
        'whatsapp_group_id_dismantle' => 'nullable|string|max:80',
        'whatsapp_group_id_relokasi' => 'nullable|string|max:80',
        'whatsapp_group_id_maintenance' => 'nullable|string|max:80',
    ];

    private const CHECKBOXES = ['enable_partner_module', 'enable_fup_module', 'enable_sales_commission_module'];

    public function index()
    {
        $settings = Setting::all()->keyBy('key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate(self::RULES + [
            'company_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'app_icon' => 'nullable|file|mimes:png,ico,svg|max:1024',
        ], [], [
            'company_name' => 'nama perusahaan', 'company_tax_id' => 'NPWP', 'company_website' => 'website',
            'company_domain' => 'akhiran username', 'company_logo' => 'logo', 'app_icon' => 'ikon aplikasi',
            'default_commission_rate' => 'komisi mitra', 'default_sales_commission_rate' => 'insentif sales',
        ]);

        foreach (['company_logo' => 'company_logo', 'app_icon' => 'app_icon'] as $field => $key) {
            if ($request->hasFile($field)) {
                $old = Setting::where('key', $key)->value('value');
                Setting::updateOrCreate(['key' => $key], ['value' => $request->file($field)->store('company', 'public'), 'type' => 'image']);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }

        foreach (array_keys(self::RULES) as $key) {
            $value = in_array($key, self::CHECKBOXES, true)
                ? ($request->boolean($key) ? '1' : '0')
                : (string) ($data[$key] ?? '');
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string']);
        }

        Cache::forget('app_settings');

        return redirect()->route('settings.index')->with('success', 'Pengaturan aplikasi disimpan.');
    }
}
