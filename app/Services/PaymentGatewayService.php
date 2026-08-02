<?php

namespace App\Services;

use App\Models\Gateway;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Generate or return an existing payment transaction URL for the given invoice.
     * Supports specific gateway selection.
     */
    public function createTransaction(Invoice $invoice, $provider = null): ?string
    {
        // 1. If we already have a generated URL for THIS specific provider, return it
        if ($invoice->payment_url && $invoice->payment_method === $provider) {
            return $invoice->payment_url;
        }

        // 2. Locate the requested gateway OR the first active one if null
        $query = Gateway::where('type', 'payment')->where('is_active', true);
        
        if ($provider) {
            $query->where('provider', $provider);
        }

        $gateway = $query->first();

        if (!$gateway) {
            throw new \Exception($provider ? "Gateway '{$provider}' is not configured or active." : 'No active Payment Gateway found.');
        }

        // Route to specific gateway provider logic
        switch (strtolower($gateway->provider)) {
            case 'midtrans':
                return $this->handleMidtrans($invoice, $gateway->credentials);
            case 'xendit':
                return $this->handleXendit($invoice, $gateway->credentials);
            case 'duitku':
                return $this->handleDuitku($invoice, $gateway->credentials);
            default:
                throw new \Exception('Unsupported payment provider: ' . $gateway->provider);
        }
    }

    /**
     * Handle Midtrans Sandbox HTTP Request internally
     */
    protected function handleMidtrans(Invoice $invoice, array $credentials): ?string
    {
        $serverKey = $credentials['server_key'] ?? null;
        $environment = $credentials['environment'] ?? 'sandbox';
        $isProduction = ($environment === 'production');

        if (!$serverKey) {
            throw new \Exception('Midtrans Server Key is missing in Integration settings.');
        }

        // Base URL depending on environment
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        // Set up the robust HTTP call
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($serverKey . ':') // Midtrans uses basic auth with empty password
        ])->post($baseUrl, [
            'transaction_details' => [
                'order_id' => $invoice->invoice_number . '-' . time(),
                'gross_amount' => (int) $invoice->amount,
            ],
            'customer_details' => [
                'first_name' => $invoice->customer->name,
                'email' => $invoice->customer->email ?? 'customer@example.com',
                'phone' => $invoice->customer->phone ?? '08123456789',
            ],
            // You can also add callback configs or expiration here
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            // Save the URL to DB to prevent regenerating on second click
            $invoice->update([
                'payment_url' => $data['redirect_url'],
                'payment_token' => $data['token'],
                'payment_method' => 'midtrans'
            ]);

            return $data['redirect_url'];
        }

        // If something goes wrong, log it and throw an exception
        Log::error('Midtrans API Error: ' . $response->body());
        throw new \Exception('Failed to generate payment link: ' . $response->body());
    }

    /**
     * Handle Xendit HTTP Request
     */
    protected function handleXendit(Invoice $invoice, array $credentials): ?string
    {
        $secretKey = $credentials['secret_key'] ?? null;

        if (!$secretKey) {
            throw new \Exception('Xendit Secret Key is missing in Integration settings.');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($secretKey . ':')
        ])->post('https://api.xendit.co/v2/invoices', [
            'external_id' => $invoice->invoice_number . '-' . time(),
            'amount' => (int) $invoice->amount,
            'description' => 'Pembayaran Invoice ' . $invoice->invoice_number,
            'payer_email' => $invoice->customer->email ?? 'customer@example.com',
            'customer' => [
                'given_names' => $invoice->customer->name,
                'mobile_number' => $invoice->customer->phone ?? '08123456789',
            ],
            'success_redirect_url' => route('customer.invoices'),
            'failure_redirect_url' => route('customer.invoices'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $invoice->update([
                'payment_url' => $data['invoice_url'],
                'payment_token' => $data['id'],
                'payment_method' => 'xendit'
            ]);

            return $data['invoice_url'];
        }

        Log::error('Xendit API Error: ' . $response->body());
        throw new \Exception('Failed to generate payment link: ' . $response->body());
    }

    /**
     * Handle Duitku HTTP Request
     */
    protected function handleDuitku(Invoice $invoice, array $credentials): ?string
    {
        $merchantCode = $credentials['merchant_code'] ?? null;
        $apiKey = $credentials['api_key'] ?? null;
        $environment = $credentials['environment'] ?? 'sandbox';
        $isProduction = ($environment === 'production');

        if (!$merchantCode || !$apiKey) {
            throw new \Exception('Duitku Merchant Code or API Key is missing in Integration settings.');
        }

        $baseUrl = $isProduction 
            ? 'https://passport.duitku.com/webapi/api/merchant/v2/invoices' 
            : 'https://sandbox.duitku.com/webapi/api/merchant/v2/invoices';

        $merchantOrderId = $invoice->invoice_number . '-' . time();
        $amount = (int) $invoice->amount;
        $signature = hash('sha256', $merchantCode . $merchantOrderId . $amount . $apiKey);

        $response = Http::post($baseUrl, [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Tagihan Internet ' . $invoice->invoice_number,
            'email' => $invoice->customer->email ?? 'customer@example.com',
            'paymentMethod' => '',
            'returnUrl' => route('customer.invoices'),
            'callbackUrl' => route('webhooks.duitku'),
            'signature' => $signature,
            'expiryPeriod' => 1440,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            // Check for success code
            if (isset($data['statusCode']) && $data['statusCode'] === '00') {
                $invoice->update([
                    'payment_url' => $data['paymentUrl'],
                    'payment_token' => $data['reference'] ?? $merchantOrderId,
                    'payment_method' => 'duitku'
                ]);

                return $data['paymentUrl'];
            }
            
            throw new \Exception('Duitku API response status error: ' . ($data['statusMessage'] ?? 'Unknown Error'));
        }

        Log::error('Duitku API Error: ' . $response->body());
        throw new \Exception('Failed to generate payment link: ' . $response->body());
    }
}
