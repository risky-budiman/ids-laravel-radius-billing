<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booster;
use App\Models\CustomerBooster;
use App\Models\Invoice;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class BoosterController extends Controller
{
    public function index()
    {
        $boosters = Booster::where('is_active', true)->get();
        return response()->json([
            'boosters' => $boosters
        ]);
    }

    public function show($id)
    {
        $booster = Booster::findOrFail($id);
        
        $activeGateways = \App\Models\Gateway::where('type', 'payment')
            ->where('is_active', true)
            ->get()
            ->map(function ($gateway) {
                return [
                    'provider' => $gateway->provider,
                    'name' => strtoupper($gateway->provider),
                ];
            });

        return response()->json([
            'booster' => $booster,
            'active_gateways' => $activeGateways
        ]);
    }

    public function buy(Request $request, $id, PaymentGatewayService $paymentService)
    {
        $request->validate([
            'gateway' => 'required|string',
        ]);

        $customer = $request->user();
        $booster = Booster::findOrFail($id);

        // Find selected payment gateway
        $gateway = \App\Models\Gateway::where('type', 'payment')
            ->where('provider', $request->gateway)
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            return response()->json([
                'message' => 'Payment gateway tidak aktif atau tidak ditemukan.'
            ], 400);
        }

        // Create purchase record with unpaid status
        $purchase = CustomerBooster::create([
            'customer_id' => $customer->id,
            'booster_id' => $booster->id,
            'amount_paid' => $booster->price,
            'payment_status' => 'unpaid',
            'paid_at' => null,
        ]);

        // Create temporary unpaid invoice for this purchase
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-BOOST-' . $purchase->id,
            'billing_period' => now()->format('F Y'),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'amount' => $booster->price,
            'subtotal' => $booster->price,
            'status' => 'unpaid',
            'due_date' => now()->addDays(1),
            'notes' => "Pembelian Booster Kuota: {$booster->name}",
        ]);

        try {
            // Generate payment URL using the selected gateway
            $paymentUrl = $paymentService->createTransaction($invoice, $gateway->provider);

            return response()->json([
                'message' => "Invoice pembelian Booster {$booster->name} berhasil dibuat.",
                'payment_url' => $paymentUrl,
                'invoice' => $invoice,
            ]);
        } catch (\Throwable $e) {
            // Rollback if payment link generation fails
            $invoice->delete();
            $purchase->delete();

            return response()->json([
                'message' => 'Gagal memproses transaksi pembayaran: ' . $e->getMessage()
            ], 400);
        }
    }
}
