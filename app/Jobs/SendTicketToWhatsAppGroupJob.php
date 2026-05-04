<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTicketToWhatsAppGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ticket;
    protected $action;

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket, $action = 'created')
    {
        $this->ticket = $ticket;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        // 1. Determine the target group based on ticket type
        $type = $this->ticket->type ?? 'gangguan';
        $settingKey = "whatsapp_group_id_{$type}";
        $groupId = get_setting($settingKey) ?: get_setting('whatsapp_group_id');

        if (!$groupId) {
            Log::warning("WhatsApp Ticket Queue: No target group ID found for type '{$type}' or fallback.");
            return;
        }

        // Ensure customer relation is loaded
        $this->ticket->loadMissing('customer');
        $customer = $this->ticket->customer;
        $customerName = $customer->name ?? 'Unknown';
        $customerPhone = $customer->phone ?? '-';
        $customerAddress = $customer->address ?? '-';
        $status = strtoupper($this->ticket->status);
        $priority = strtoupper($this->ticket->priority);
        
        $title = $this->action === 'created' ? "🎫 *TIKET BARU*" : "🔄 *UPDATE TIKET*";
        
        $message = "{$title}\n\n";
        $message .= "*No Tiket:* {$this->ticket->ticket_number}\n";
        $message .= "*Status:* {$status} | *Prioritas:* {$priority}\n";
        $message .= "*Subjek:* {$this->ticket->subject}\n";
        $message .= "--------------------------------\n";
        $message .= "*DATA PELANGGAN*\n";
        $message .= "👤 *Nama:* {$customerName}\n";
        $message .= "📞 *HP:* {$customerPhone}\n";
        $message .= "📍 *Alamat:* {$customerAddress}\n";
        
        // Add Coordinates & Maps Link if available
        if ($customer && $customer->latitude && $customer->longitude) {
            $mapsLink = "https://www.google.com/maps/search/?api=1&query={$customer->latitude},{$customer->longitude}";
            $message .= "🌎 *Lokasi:* {$customer->latitude},{$customer->longitude}\n";
            $message .= "🗺️ *Navigasi:* {$mapsLink}\n";
        }

        $message .= "--------------------------------\n";
        $message .= "*Deskripsi:*\n{$this->ticket->description}\n\n";
        
        if ($this->ticket->resolution_notes) {
            $message .= "*Catatan Resolusi:*\n{$this->ticket->resolution_notes}\n\n";
        }

        $message .= "--- Logged at " . now()->format('H:i:s d/m/Y') . " ---";

        $waService->sendMessage($groupId, $message);
    }
}
