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

        // Ensure customer and related data are loaded
        $this->ticket->loadMissing(['customer.package', 'customer.sales']);
        
        $customer = $this->ticket->customer;
        $customerName = $customer->name ?? 'Unknown';
        $customerPhone = $customer->phone ?? '-';
        $customerAddress = $customer->address ?? '-';
        $customerEmail = $customer->email ?? '-';
        $status = strtoupper($this->ticket->status);
        $priority = strtoupper($this->ticket->priority);
        
        // Package Information
        $package = $customer->package ?? null;
        $packageInfo = "-";
        if ($package) {
            $basePrice = $package->price;
            $finalPrice = $basePrice;
            $taxNote = "belum termasuk PPN 11%";

            if ($customer->use_tax) {
                $finalPrice = $basePrice * 1.11; // Add 11% PPN
                $taxNote = "sudah termasuk PPN 11%";
            }

            $formattedPrice = number_format($finalPrice, 0, ',', '.');
            $packageInfo = "{$package->name} (Rp. {$formattedPrice}/bln, {$taxNote})";
        }

        // Sales Referral
        $salesName = $customer->sales->name ?? '-';

        $actionTitle = $this->action === 'created' ? "TIKET BARU" : "UPDATE TIKET";
        if (in_array(strtolower($this->ticket->status), ['solved', 'closed', 'resolved'])) {
            $actionTitle = "TIKET SELESAI / SOLVED";
        }
        
        // New Format Implementation
        $message = "📝 *KETERANGAN ORDER [{$actionTitle}]*\n\n";
        $message .= "*STATUS:* {$status}\n";
        $message .= "*AO ID/NO TIKET:* {$this->ticket->ticket_number}\n";
        $message .= "*PRIORITAS:* {$priority}\n";
        $message .= "--------------------------------\n";
        $message .= "*Nama Lengkap :* {$customerName}\n";
        $message .= "*HP :* {$customerPhone}\n";
        $message .= "*Alamat :* {$customerAddress}\n";
        $message .= "*Email :* {$customerEmail}\n";
        $message .= "*Paket Layanan :* {$packageInfo}\n";
        
        // Add Coordinates if available
        if ($customer && $customer->latitude && $customer->longitude) {
            $message .= "*Koordinat :* {$customer->latitude}, {$customer->longitude}\n";
        }

        $message .= "--------------------------------\n";
        $message .= "*Deskripsi :*\n{$this->ticket->description}\n";
        $message .= "--------------------------------\n";
        $message .= "*Sales Referal :* {$salesName}\n";
        
        if ($this->ticket->resolution_notes) {
            $message .= "*Note :* {$this->ticket->resolution_notes}\n";
        }

        $waService->sendMessage($groupId, $message);
    }
}
