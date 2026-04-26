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

        $gateway = Gateway::where('provider', 'fonnte')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            $error = 'WhatsApp Notification: Fonnte is not active or configured.';
            Log::warning($error);
            if ($log) $log->update(['status' => 'failed', 'error_reason' => $error]);
            return false;
        }

        $token = $gateway->credentials['token'] ?? null;

        if (!$token) {
            $error = 'WhatsApp Notification: Fonnte token is missing.';
            Log::warning($error);
            if ($log) $log->update(['status' => 'failed', 'error_reason' => $error]);
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
                if ($log) $log->update(['status' => 'sent']);
                return true;
            }

            $error = 'Fonnte API Error: ' . $response->body();
            Log::error($error);
            if ($log) $log->update(['status' => 'failed', 'error_reason' => $error]);
            return false;

        } catch (\Exception $e) {
            $error = 'WhatsApp Service Exception: ' . $e->getMessage();
            Log::error($error);
            if ($log) $log->update(['status' => 'failed', 'error_reason' => $error]);
            return false;
        }
    }
}
