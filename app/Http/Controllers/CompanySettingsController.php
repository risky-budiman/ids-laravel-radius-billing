<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    public function index()
    {
        return view('settings.company');
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
        ]);

        $settings = $request->only([
            'company_name', 
            'company_address', 
            'company_phone', 
            'company_email'
        ]);

        // Handle Logo Upload
        if ($request->hasFile('company_logo')) {
            // Delete old logo if exists
            $oldLogo = get_setting('company_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('company_logo')->store('company', 'public');
            $settings['company_logo'] = $path;
        }

        // Update settings in database
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => str_contains($key, 'logo') ? 'image' : 'string']
            );
        }

        // Clear Cache
        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Profil perusahaan berhasil diperbarui.');
    }
}
