<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Gateway;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();
        
        $query = $customer->invoices();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function show(Request $request, $id)
    {
        $customer = $request->user();
        $invoice = $customer->invoices()->findOrFail($id);

        // Get active gateways so client can choose
        $activeGateways = Gateway::where('type', 'payment')
            ->where('is_active', true)
            ->get()
            ->map(function ($gateway) {
                return [
                    'provider' => $gateway->provider,
                    'name' => strtoupper($gateway->provider),
                ];
            });

        return response()->json([
            'invoice' => $invoice,
            'active_gateways' => $activeGateways
        ]);
    }

    public function pay(Request $request, $id, PaymentGatewayService $paymentService)
    {
        $request->validate([
            'gateway' => 'required|string',
        ]);

        $customer = $request->user();
        $invoice = $customer->invoices()->where('status', 'unpaid')->findOrFail($id);

        try {
            $paymentUrl = $paymentService->createTransaction($invoice, $request->gateway);
            
            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'payment_url' => $paymentUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 400);
        }
    }
}
