<?php

namespace App\Services\Network;

use App\Jobs\SendCustomWhatsappMessageJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Peringatan NOC ke Telegram dan/atau WhatsApp (nomor atau grup, Integrasi › NOC Bot).
 * Dipanggil oleh noc:monitor (dulu layanan ini tidak dipanggil di mana pun).
 */
class NocAlertService
{
    public function configured(): bool
    {
        return (get_setting('telegram_bot_token') && get_setting('telegram_noc_chat_id')) || get_setting('whatsapp_noc_number');
    }

    public function sendAlert(string $message): void
    {
        $this->sendToTelegram($message);
        $this->sendToWhatsApp($message);
    }

    protected function sendToTelegram(string $message): void
    {
        $token = get_setting('telegram_bot_token');
        $chatId = get_setting('telegram_noc_chat_id');
        if (! $token || ! $chatId) {
            return;
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "⚠️ NOC ALERT\n\n" . $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram Alert Error: ' . $e->getMessage());
        }
    }

    protected function sendToWhatsApp(string $message): void
    {
        $target = get_setting('whatsapp_noc_number');
        if (! $target) {
            return;
        }
        // Lewat antrean (jeda anti-ban) seperti peringatan tiket; target boleh nomor atau ID grup
        SendCustomWhatsappMessageJob::dispatch($target, "⚠️ *NOC ALERT*\n\n" . $message);
    }
}
