<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Gateway;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Show the public invoice portal.
     * Supports Route Model Binding, Invoice ID, or Invoice Number.
     */
    public function showInvoice(Request $request, $invoice)
    {
        if (!$invoice instanceof Invoice) {
            $invoice = Invoice::where('invoice_number', $invoice)
                ->orWhere('id', $invoice)
                ->firstOrFail();
        }

        $activeGateways = Gateway::where('type', 'payment')
            ->where('is_active', true)
            ->get();

        return view('portal.invoice-portal', compact('invoice', 'activeGateways'));
    }

    /**
     * Universal short payment link (/pay/{invoice})
     */
    public function pay(Request $request, $invoice)
    {
        return $this->showInvoice($request, $invoice);
    }
}
