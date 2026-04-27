<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ClosingController extends Controller
{
    public function index()
    {
        $closedUntil = get_setting('accounting_closed_until');
        return view('accounting.closing.index', compact('closedUntil'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'closed_until' => 'required|date',
            'confirmation' => 'required|accepted',
        ]);

        // Save the setting
        $setting = Setting::where('key', 'accounting_closed_until')->first();
        if ($setting) {
            $setting->update(['value' => $request->closed_until]);
        } else {
            Setting::create([
                'key' => 'accounting_closed_until',
                'value' => $request->closed_until,
                'group' => 'accounting',
                'type' => 'date'
            ]);
        }

        return back()->with('success', 'Periode akuntansi berhasil dikunci hingga tanggal ' . \Carbon\Carbon::parse($request->closed_until)->format('d F Y') . '. Transaksi sebelum tanggal ini tidak dapat diubah.');
    }
}
