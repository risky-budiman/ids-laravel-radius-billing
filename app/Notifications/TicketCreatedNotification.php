<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    protected $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $hasReplies = $this->ticket->replies()->whereNotNull('user_id')->orWhereNull('user_id')->count() > 0;
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
}
