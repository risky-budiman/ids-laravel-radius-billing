<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        // Check if the last reply was from the customer (user_id is null)
        $lastReply = $this->ticket->replies()->latest()->first();
        $ticketNum = $this->ticket->ticket_number ?? ('#' . $this->ticket->id);
        
        if ($lastReply && $lastReply->user_id === null && !$lastReply->is_system) {
            $msg = "💬 Balasan Baru Tiket {$ticketNum} dari pelanggan";
        } else {
            $msg = "🎫 Tiket Baru {$ticketNum}: {$this->ticket->subject}";
        }

        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'message' => $msg,
            'url' => route('tickets.show', $this->ticket->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => static::class,
            'data' => $this->toArray($notifiable),
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
