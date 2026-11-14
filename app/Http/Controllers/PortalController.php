<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Gateway;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Show the public invoice portal.
     *
     * Pencarian invoice:
     *  - Nomor invoice (link WhatsApp: /pay/{invoice_number}) — selalu diizinkan.
     *  - ID numerik — HANYA bila URL ditandatangani (URL::signedRoute), agar data
     *    pelanggan tidak bisa diintip dengan menebak /portal/invoice/1, /2, /3, ...
     */
    public function showInvoice(Request $request, $invoice)
    {
        if (! $invoice instanceof Invoice) {
            $invoice = Invoice::where('invoice_number', (string) $invoice)->first()
                ?? ($request->hasValidSignature() && ctype_digit((string) $invoice)
                    ? Invoice::find((int) $invoice)
                    : null);

            abort_if(! $invoice, 404);
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
