<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gateway;

class IntegrationController extends Controller
{
    public function payment()
    {
        $gateways = Gateway::where('type', 'payment')->get()->keyBy('provider');
        return view('integrations.payment', compact('gateways'));
    }

    public function whatsapp()
    {
        $gateways = Gateway::where('type', 'whatsapp')->get()->keyBy('provider');
        return view('integrations.whatsapp', compact('gateways'));
    }

    public function nocBot()
    {
        return view('integrations.noc-bot');
    }

    public function updateNocBot(Request $request)
    {
        $data = $request->only(['telegram_bot_token', 'telegram_noc_chat_id', 'whatsapp_noc_number', 'whatsapp_noc_target_type']);

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        \Illuminate\Support\Facades\Cache::forget('app_settings');

        return redirect()->back()->with('success', 'NOC Bot settings updated successfully!');
    }

    public function updateAntiBan(Request $request)
    {
        $request->validate([
            'whatsapp_delay_min' => 'required|integer|min:1|max:60',
            'whatsapp_delay_max' => 'required|integer|min:1|max:120',
        ]);

        $min = (int) $request->whatsapp_delay_min;
        $max = (int) $request->whatsapp_delay_max;

        if ($min > $max) {
            return redirect()->back()->withErrors(['whatsapp_delay_max' => 'Jeda maksimal harus lebih besar atau sama dengan jeda minimal.']);
        }

        \App\Models\Setting::updateOrCreate(
            ['key' => 'whatsapp_delay_min'],
            ['value' => (string) $min, 'type' => 'integer']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'whatsapp_delay_max'],
            ['value' => (string) $max, 'type' => 'integer']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'whatsapp_enable_delay'],
            ['value' => $request->has('whatsapp_enable_delay') ? '1' : '0', 'type' => 'boolean']
        );

        \Illuminate\Support\Facades\Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Pengaturan Proteksi Interval Anti-Ban WhatsApp berhasil diperbarui.');
    }

    public function update(Request $request)
    {
        // Example dynamic payload format:
        // provider: 'midtrans', type: 'payment', is_active: 1, credentials: { server_key: '...', client_key: '...' }
        $request->validate([
            'provider' => 'required|string',
            'type' => 'required|in:payment,whatsapp',
            'is_active' => 'boolean',
            'credentials' => 'array',
        ]);

        Gateway::updateOrCreate(
            ['provider' => $request->provider],
            [
                'type' => $request->type,
                'credentials' => $request->credentials ?? [],
                'is_active' => $request->is_active ?? false,
            ]
        );

        // Auto-sync Bank Account for Payment Gateways
        if ($request->type === 'payment' && $request->is_active) {
            \App\Models\BankAccount::updateOrCreate(
                ['bank_name' => strtoupper($request->provider)],
                [
                    'account_name' => 'Gateway ' . strtoupper($request->provider),
                    'type' => 'payment_gateway',
                    'is_active' => true,
                    // Note: Initial balance is handled by transactions
                ]
            );
        }

        return redirect()->back()->with('success', strtoupper($request->provider) . ' Gateway settings updated successfully!');
    }
}
