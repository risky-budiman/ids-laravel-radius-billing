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

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        // Handle Midtrans Test Webhook / Notification Simulation or Pay Account/Recurring URL checks
        if (empty($orderId) || str_starts_with($orderId, 'payment_notif_test_')) {
            Log::info('Midtrans Webhook notification received (Test/Special Event) and skipped from processing: ' . ($orderId ?: 'No Order ID'));
            return response()->json(['message' => 'Notification processed successfully']);
        }

        // Extract Invoice Number from Order ID (handling the timestamp suffix if exists)
        $lastHyphenPos = strrpos($orderId, '-');
        $invoiceNumber = ($lastHyphenPos !== false) ? substr($orderId, 0, $lastHyphenPos) : $orderId;
        
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            Log::error('Invoice not found for Order ID: ' . $orderId);
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Verify Signature (Recommended for Security)
        $serverKey = $this->gatewayCredential('midtrans', 'server_key');
        if (! $serverKey) {
            return $this->rejectUnconfigured('midtrans');
        }
        $computedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if (! hash_equals($computedSignature, (string) $signatureKey)) {
            Log::error('Invalid Midtrans Signature for Order ID: ' . $orderId);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Handle Status
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            if ($held = $this->holdIfUnderpaid($invoice, $grossAmount, 'Midtrans', $orderId)) {
                return $held;
            }
            if ($invoice->status !== 'paid') {
                try {
                    $pgAccount = BankAccount::where('bank_name', 'MIDTRANS')->first();
                    if (!$pgAccount) {
                        $pgAccount = BankAccount::create([
                            'bank_name' => 'MIDTRANS',
                            'account_name' => 'Gateway MIDTRANS',
                            'type' => 'payment_gateway',
                            'is_active' => true,
                        ]);
                    }
                    
                    app(\App\Services\InvoiceService::class)->markAsPaid($invoice, $pgAccount->id);
                } catch (\Exception $e) {
                    Log::error('Webhook Payment Processing Failed: ' . $e->getMessage());
                    if (app()->environment('testing')) {
                        throw $e;
                    }
                }
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle Xendit Webhook
     */
    public function xendit(Request $request)
    {
        $payload = $request->all();
        Log::info('Xendit Webhook Received: ', $payload);

        $orderId = $payload['external_id'] ?? '';
        $status = strtoupper($payload['status'] ?? '');

        if (empty($orderId)) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        // Verify Callback Token (Security)
        $token = $this->gatewayCredential('xendit', 'callback_token');
        if (! $token) {
            return $this->rejectUnconfigured('xendit');
        }
        if (! hash_equals($token, (string) $request->header('x-callback-token'))) {
            Log::error('Invalid Xendit Callback Token');
            return response()->json(['message' => 'Invalid token'], 403);
        }

        // Extract Invoice Number from Order ID
        $lastHyphenPos = strrpos($orderId, '-');
        $invoiceNumber = ($lastHyphenPos !== false) ? substr($orderId, 0, $lastHyphenPos) : $orderId;

        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            Log::error('Invoice not found for Xendit External ID: ' . $orderId);
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Handle Status
        if (in_array($status, ['PAID', 'SETTLED'])) {
            if ($held = $this->holdIfUnderpaid($invoice, $payload['paid_amount'] ?? $payload['amount'] ?? null, 'Xendit', $orderId)) {
                return $held;
            }
            if ($invoice->status !== 'paid') {
                try {
                    $pgAccount = BankAccount::where('bank_name', 'XENDIT')->first();
                    if (!$pgAccount) {
                        $pgAccount = BankAccount::create([
                            'bank_name' => 'XENDIT',
                            'account_name' => 'Gateway XENDIT',
                            'type' => 'payment_gateway',
                            'is_active' => true,
                        ]);
                    }

                    app(\App\Services\InvoiceService::class)->markAsPaid($invoice, $pgAccount->id);
                } catch (\Exception $e) {
                    Log::error('Xendit Webhook Payment Processing Failed: ' . $e->getMessage());
                    if (app()->environment('testing')) {
                        throw $e;
                    }
                }
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle Duitku Webhook
     */
    public function duitku(Request $request)
    {
        $payload = $request->all();
        Log::info('Duitku Webhook Received: ', $payload);

        $merchantCode = $payload['merchantCode'] ?? '';
        $amount = $payload['amount'] ?? '';
        $merchantOrderId = $payload['merchantOrderId'] ?? '';
        $resultCode = $payload['resultCode'] ?? '';
        $signature = $payload['signature'] ?? '';

        if (empty($merchantOrderId)) {
            return response()->json(['message' => 'Missing merchantOrderId'], 400);
        }

        // Verify Signature
        $apiKey = $this->gatewayCredential('duitku', 'api_key');
        if (! $apiKey) {
            return $this->rejectUnconfigured('duitku');
        }

        // Duitku v2 uses SHA256, legacy uses MD5. Let's support both.
        $computedSha256 = hash('sha256', $merchantCode . $amount . $merchantOrderId . $apiKey);
        $computedMd5 = md5($merchantCode . $amount . $merchantOrderId . $apiKey);

        if (! hash_equals($computedSha256, (string) $signature) && ! hash_equals($computedMd5, (string) $signature)) {
            Log::error('Invalid Duitku Signature');
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Extract Invoice Number from merchantOrderId
        $lastHyphenPos = strrpos($merchantOrderId, '-');
        $invoiceNumber = ($lastHyphenPos !== false) ? substr($merchantOrderId, 0, $lastHyphenPos) : $merchantOrderId;

        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            Log::error('Invoice not found for Duitku Order ID: ' . $merchantOrderId);
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Handle Status (00 = success)
        if ($resultCode === '00') {
            if ($held = $this->holdIfUnderpaid($invoice, $amount, 'Duitku', $merchantOrderId)) {
                return $held;
            }
            if ($invoice->status !== 'paid') {
                try {
                    $pgAccount = BankAccount::where('bank_name', 'DUITKU')->first();
                    if (!$pgAccount) {
                        $pgAccount = BankAccount::create([
                            'bank_name' => 'DUITKU',
                            'account_name' => 'Gateway DUITKU',
                            'type' => 'payment_gateway',
                            'is_active' => true,
                        ]);
                    }

                    app(\App\Services\InvoiceService::class)->markAsPaid($invoice, $pgAccount->id);
                } catch (\Exception $e) {
                    Log::error('Duitku Webhook Payment Processing Failed: ' . $e->getMessage());
                    if (app()->environment('testing')) {
                        throw $e;
                    }
                }
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle Moota Webhook
     */
    public function moota(Request $request)
    {
        $payload = $request->json()->all();
        Log::info('Moota Webhook Received: ', $payload);

        if (!is_array($payload)) {
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        // Verifikasi signature Moota (wajib): HMAC-SHA256 dari raw body memakai secret token webhook.
        $secret = $this->gatewayCredential('moota', 'webhook_secret')
            ?? $this->gatewayCredential('moota', 'api_token'); // kompatibilitas konfigurasi lama
        if (! $secret) {
            return $this->rejectUnconfigured('moota');
        }
        $signature = (string) ($request->header('Signature') ?? $request->header('x-moota-signature') ?? '');
        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);
        if ($signature === '' || ! hash_equals($computedSignature, $signature)) {
            Log::warning('Moota webhook ditolak: signature tidak valid atau tidak ada.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        foreach ($payload as $mutation) {
            $type = strtoupper($mutation['type'] ?? '');
            $amount = (float) ($mutation['amount'] ?? 0);
            $description = $mutation['description'] ?? '';

            // Moota CR = Credit (incoming mutation/transfer)
            if ($type === 'CR' && $amount > 0) {
                // Find all unpaid invoices
                $invoices = Invoice::where('status', 'unpaid')
                    ->where('amount', $amount)
                    ->get();

                $matchedInvoice = null;

                if ($invoices->count() === 1) {
                    $matchedInvoice = $invoices->first();
                } elseif ($invoices->count() > 1) {
                    // Try to match based on description details (customer name, invoice number, code)
                    foreach ($invoices as $inv) {
                        if (str_contains(strtolower($description), strtolower($inv->invoice_number)) || 
                            str_contains(strtolower($description), strtolower($inv->customer->name)) ||
                            str_contains(strtolower($description), strtolower($inv->customer->customer_code ?? ''))) {
                            $matchedInvoice = $inv;
                            break;
                        }
                    }
                }

                if ($matchedInvoice) {
                    try {
                        $pgAccount = BankAccount::where('bank_name', 'MOOTA')->first();
                        if (!$pgAccount) {
                            $pgAccount = BankAccount::create([
                                'bank_name' => 'MOOTA',
                                'account_name' => 'Gateway MOOTA',
                                'type' => 'payment_gateway',
                                'is_active' => true,
                             ]);
                        }

                        app(\App\Services\InvoiceService::class)->markAsPaid($matchedInvoice, $pgAccount->id);
                        Log::info("Moota successfully auto-reconciled Invoice {$matchedInvoice->invoice_number}");
                    } catch (\Exception $e) {
                        Log::error('Moota Payment Processing Failed: ' . $e->getMessage());
                        if (app()->environment('testing')) {
                            throw $e;
                        }
                    }
                } else {
                    Log::warning("Moota mutation unmatched: Amount {$amount}, Desc: {$description}");
                }
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Ambil kredensial gateway. Null bila gateway/kredensial belum dikonfigurasi.
     */
    protected function gatewayCredential(string $provider, string $key): ?string
    {
        $gateway = \App\Models\Gateway::where('provider', $provider)->first();
        if (! $gateway) {
            return null;
        }
        $value = $gateway->credentials[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Pembayaran gateway yang kurang dari tagihan tidak boleh melunasi invoice (mis. nominal invoice
     * dinaikkan setelah link bayar dibuat, atau nominal tidak dikirim). Invoice ditahan untuk dicek
     * manual; balasan 200 agar gateway tidak terus mengirim ulang.
     */
    protected function holdIfUnderpaid(Invoice $invoice, mixed $paid, string $provider, string $reference)
    {
        if ($invoice->status === 'paid') {
            return null;
        }

        $paidAmount = is_numeric($paid) ? round((float) $paid, 2) : null;
        $due = round((float) $invoice->amount, 2);
        if ($paidAmount !== null && $paidAmount + 0.5 >= $due) {
            return null;
        }

        $shown = $paidAmount === null ? 'tanpa nominal' : 'Rp ' . number_format($paidAmount, 0, ',', '.');
        $note = "[{$provider} {$reference}] Pembayaran {$shown} kurang dari tagihan Rp " . number_format($due, 0, ',', '.')
            . ' — tidak dilunasi otomatis, perlu dicek.';
        Log::warning("Webhook {$provider}: {$invoice->invoice_number} {$note}");
        $invoice->forceFill(['notes' => trim(($invoice->notes ? $invoice->notes . PHP_EOL : '') . $note)])->save();

        return response()->json(['message' => 'Amount mismatch, held for review']);
    }

    /**
     * Fail-closed: webhook tanpa kredensial verifikasi tidak boleh mengubah status pembayaran.
     */
    protected function rejectUnconfigured(string $provider)
    {
        Log::error("Webhook {$provider} ditolak: kredensial verifikasi gateway belum diisi.");

        return response()->json(['message' => 'Gateway not configured'], 403);
    }
}
