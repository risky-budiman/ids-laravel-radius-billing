<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Midtrans Webhook
     */
    public function midtrans(Request $request)
    {
        $payload = $request->all();
        Log::info('Midtrans Webhook Received: ', $payload);

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        $transactionStatus = $payload['transaction_status'];
        $signatureKey = $payload['signature_key'];

        // Extract Invoice Number from Order ID (handling the timestamp suffix if exists)
        $invoiceNumber = explode('-', $orderId)[0];
        
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            Log::error('Invoice not found for Order ID: ' . $orderId);
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Verify Signature (Recommended for Security)
        $gateway = \App\Models\Gateway::where('provider', 'midtrans')->first();
        if ($gateway && isset($gateway->credentials['server_key'])) {
            $serverKey = $gateway->credentials['server_key'];
            $computedSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);
            
            if ($computedSignature !== $signatureKey) {
                Log::error('Invalid Midtrans Signature for Order ID: ' . $orderId);
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        // Handle Status
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            if ($invoice->status !== 'paid') {
                DB::transaction(function() use ($invoice) {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // 1. Record to PG Bank Account
                    $pgAccount = BankAccount::where('bank_name', 'MIDTRANS')->first();
                    if (!$pgAccount) {
                        $pgAccount = BankAccount::create([
                            'bank_name' => 'MIDTRANS',
                            'account_name' => 'Gateway MIDTRANS',
                            'type' => 'payment_gateway',
                            'is_active' => true,
                        ]);
                    }

                    BankTransaction::create([
                        'bank_account_id' => $pgAccount->id,
                        'type' => 'deposit',
                        'amount' => $invoice->amount,
                        'description' => '[PG SETTLEMENT] Midtrans - ' . $invoice->invoice_number,
                        'transaction_date' => now(),
                    ]);

                    // 2. Reactivate Customer if suspended
                    $customer = $invoice->customer;
                    if ($customer) {
                        $customer->update([
                            'is_active' => true,
                            'status' => Customer::STATUS_ACTIVE,
                        ]);

                        // Handle Installation specific marking
                        if (str_contains($invoice->invoice_number, 'INV-INST')) {
                            $customer->update([
                                'installation_paid_at' => now(),
                                'installation_bank_account_id' => $pgAccount->id,
                                'activation_grace_expires_at' => null, // Clear grace period
                            ]);
                        }
                    }
                });
            }
        }

        return response()->json(['message' => 'OK']);
    }
}
