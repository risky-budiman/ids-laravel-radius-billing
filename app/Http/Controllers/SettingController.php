<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', 'company_logo']);

        // Handle Logo Upload
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('company', 'public');
            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['value' => $path, 'type' => 'image']
            );
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        return redirect()->back()->with('success', 'Company profile updated successfully.');
    }
}
