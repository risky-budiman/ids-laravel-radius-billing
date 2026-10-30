<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketUpdated implements ShouldBroadcastNow
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
            'resolution_notes' => $ticket->resolution_notes,
            'assigned_to_name' => $ticket->assignee ? $ticket->assignee->name : 'Belum Ditugaskan',
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.tickets'),
            new PrivateChannel('ticket.' . $this->ticket['id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }
}

