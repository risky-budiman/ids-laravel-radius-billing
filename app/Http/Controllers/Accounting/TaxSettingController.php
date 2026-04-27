<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
{
    public function index()
    {
        $taxMode = Setting::where('key', 'tax_mode')->first()?->value ?? 'individual';
        $taxRate = \App\Models\Tax::where('is_active', true)->first()?->rate ?? 11;

        return view('accounting.tax-settings.index', compact('taxMode', 'taxRate'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tax_mode' => 'required|in:all,individual',
        ]);

        Setting::updateOrCreate(
            ['key' => 'tax_mode'],
            ['value' => $request->tax_mode, 'type' => 'string']
        );

        return back()->with('success', 'Pengaturan PPN global berhasil diperbarui.');
    }
}
