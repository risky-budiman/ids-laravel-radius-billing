<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'customer_name' => $ticket->customer ? $ticket->customer->name : 'N/A',
            'created_at' => $ticket->created_at ? $ticket->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.tickets'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }
}

