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
                // Placeholder for Xendit
                throw new \Exception('Xendit is not fully integrated yet (PoC phase). Please use Midtrans.');
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
}
