<?php

namespace App\Services\Network;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Services\WhatsAppService;

class NocAlertService
{
    /**
     * Send Alert to NOC Group via Telegram and/or WhatsApp.
     */
    public function sendAlert(string $message)
    {
        $this->sendToTelegram($message);
        $this->sendToWhatsApp($message);
    }

    protected function sendToTelegram(string $message)
    {
        $token = get_setting('telegram_bot_token');
        $chatId = get_setting('telegram_noc_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "⚠️ *NOC ALERT*\n\n" . $message,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Alert Error: " . $e->getMessage());
        }
    }

    protected function sendToWhatsApp(string $message)
    {
        $target = get_setting('whatsapp_noc_number');
        if (!$target) return;

        $waService = new WhatsAppService();
        $waService->sendMessage($target, "⚠️ *NOC ALERT*\n\n" . $message);
    }
}
