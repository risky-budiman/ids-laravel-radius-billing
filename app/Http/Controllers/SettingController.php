<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Handle Logo Upload
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('company', 'public');
            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['value' => $path, 'type' => 'image']
            );
        }

        // Handle App Icon Upload
        if ($request->hasFile('app_icon')) {
            $path = $request->file('app_icon')->store('company', 'public');
            Setting::updateOrCreate(
                ['key' => 'app_icon'],
                ['value' => $path, 'type' => 'image']
            );
        }

        $data = $request->except(['_token', 'company_logo', 'app_icon']);
        
        // Handle explicit checkbox booleans
        $checkboxes = ['enable_partner_module'];
        foreach ($checkboxes as $cb) {
            if (!$request->has($cb)) {
                $data[$cb] = '0';
            }
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        // IMPORTANT: Clear the cache so changes appear immediately
        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Company profile updated successfully.');
    }
}
