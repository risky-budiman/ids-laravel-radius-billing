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
    public function sendMessage($target, $message): bool
    {
        $gateway = Gateway::where('provider', 'fonnte')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            Log::warning('WhatsApp Notification: Fonnte is not active or configured.');
            return false;
        }

        $token = $gateway->credentials['token'] ?? null;

        if (!$token) {
            Log::warning('WhatsApp Notification: Fonnte token is missing.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Default to Indonesia
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Fonnte API Error: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception: ' . $e->getMessage());
            return false;
        }
    }
}
