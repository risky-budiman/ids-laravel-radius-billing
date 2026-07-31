<?php

namespace App\Services;

use App\Models\Gateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message using Fonnte.
     */
    public function sendMessage($target, $message, $logId = null): bool
    {
        if (empty($target)) {
            Log::warning("WhatsApp Notification: target phone number is empty. Message was: " . substr($message, 0, 100) . "...");
            return false;
        }

        $log = null;
        if ($logId) {
            $log = \App\Models\WhatsappLog::find($logId);
        } else {
            $log = \App\Models\WhatsappLog::create([
                'target_phone' => $target,
                'message' => $message,
                'status' => 'pending',
            ]);
        }

        // Get all active gateways ordered by latest updated or a specific logic
        $gateways = Gateway::where('type', 'whatsapp')
            ->where('is_active', true)
            ->get();

        if ($gateways->isEmpty()) {
            $error = 'WhatsApp Notification: No active gateway configured.';
            Log::warning($error);
            if ($log) $log->update(['status' => 'failed', 'error_reason' => $error]);
            return false;
        }

        $lastError = '';
        foreach ($gateways as $gateway) {
            $success = false;
            
            if ($gateway->provider === 'fonnte') {
                $success = $this->sendViaFonnte($gateway, $target, $message);
            } elseif ($gateway->provider === 'wablas') {
                $success = $this->sendViaWablas($gateway, $target, $message);
            } elseif ($gateway->provider === 'starsender') {
                $success = $this->sendViaStarsender($gateway, $target, $message);
            } elseif ($gateway->provider === 'mekari') {
                $success = $this->sendViaMekari($gateway, $target, $message);
            } elseif ($gateway->provider === 'self_hosted') {
                $success = $this->sendViaSelfHosted($target, $message);
            }

            if ($success) {
                if ($log) $log->update(['status' => 'sent', 'provider' => $gateway->provider]);
                return true;
            } else {
                $lastError .= "[{$gateway->provider} Failed] ";
            }
        }

        if ($log) $log->update(['status' => 'failed', 'error_reason' => $lastError ?: 'All gateways failed.']);
        return false;
    }

    protected function sendViaFonnte($gateway, $target, $message)
    {
        $token = $gateway->credentials['token'] ?? null;
        try {
            $response = Http::timeout(10)->withHeaders(['Authorization' => $token])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
            ]);
            return $response->successful();
        } catch (\Exception $e) { return false; }
    }

    protected function sendViaWablas($gateway, $target, $message)
    {
        $domain = $gateway->credentials['domain'] ?? 'https://console.wablas.com';
        $token = $gateway->credentials['token'] ?? null;
        try {
            $response = Http::timeout(10)->withHeaders(['Authorization' => $token])->post($domain . '/api/send-message', [
                'phone' => $target,
                'message' => $message,
            ]);
            return $response->successful();
        } catch (\Exception $e) { return false; }
    }

    protected function sendViaStarsender($gateway, $target, $message)
    {
        $token = $gateway->credentials['token'] ?? null;
        try {
            // Starsender API Endpoint
            $response = Http::timeout(10)->withHeaders(['Authorization' => $token])->post('https://starsender.online/api/sendText', [
                'tujuan' => $target,
                'message' => $message,
            ]);
            return $response->successful();
        } catch (\Exception $e) { return false; }
    }

    protected function sendViaMekari($gateway, $target, $message)
    {
        $token = $gateway->credentials['token'] ?? null;
        $channelId = $gateway->credentials['channel_id'] ?? null;
        
        try {
            // Mekari Qontak Direct Message API
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post('https://api-service.qontak.com/api/open/v1/broadcasts/whatsapp/direct', [
                'to_number' => $target,
                'to_name' => $target,
                'message_template_id' => $gateway->credentials['template_id'] ?? '', // Mekari strictly uses templates
                'channel_integration_id' => $channelId,
                'language' => ['code' => 'id'],
                'parameters' => [
                    'body' => [
                        ['key' => '1', 'value' => $message]
                    ]
                ]
            ]);
            return $response->successful();
        } catch (\Exception $e) { return false; }
    }

    protected function sendViaSelfHosted($target, $message)
    {
        $url = env('WA_GATEWAY_URL', 'http://localhost:3100/api');
        $token = env('WA_GATEWAY_KEY', 'dev-wa-gateway-key-2026');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $token])
                ->post($url . '/send', [
                    'phone' => $target,
                    'message' => $message,
                ]);
            return $response->successful();
        } catch (\Exception $e) { 
            Log::error('Self-hosted WA Gateway Error: ' . $e->getMessage());
            return false; 
        }
    }
}
