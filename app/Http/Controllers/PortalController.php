<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Gateway;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Show the public invoice portal using a signed URL.
     */
    public function showInvoice(Request $request, Invoice $invoice)
    {
        // Double check signed signature (though middleware usually handles this)
        if (!$request->hasValidSignature()) {
            abort(403, 'Link kadaluarsa atau tidak sah.');
        }

        $activeGateways = Gateway::where('type', 'payment')
            ->where('is_active', true)
            ->get();

        return view('portal.invoice-portal', compact('invoice', 'activeGateways'));
    }
}
