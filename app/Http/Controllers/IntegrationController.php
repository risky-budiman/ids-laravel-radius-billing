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

        return redirect()->back()->with('success', strtoupper($request->provider) . ' Gateway settings updated successfully!');
    }
}
