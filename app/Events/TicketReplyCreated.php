<?php

namespace App\Events;

use App\Models\TicketReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReplyCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply;

    public function __construct(TicketReply $reply)
    {
        $ticket = $reply->ticket;
        $this->reply = [
            'id' => $reply->id,
            'ticket_id' => $reply->ticket_id,
            'ticket_number' => $ticket ? $ticket->ticket_number : '',
            'user_id' => $reply->user_id,
            'user_name' => $reply->user ? $reply->user->name : ($ticket && $ticket->customer ? $ticket->customer->name . ' (Pelanggan)' : 'Customer'),
            'is_staff' => (bool) $reply->user_id,
            'is_system' => (bool) $reply->is_system,
            'message' => $reply->message,
            'attachment_url' => $reply->attachment ? asset('storage/' . $reply->attachment) : null,
            'created_at' => $reply->created_at ? $reply->created_at->diffForHumans() : 'baru saja',
            'created_at_raw' => $reply->created_at ? $reply->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.tickets'),
            new PrivateChannel('ticket.' . $this->reply['ticket_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.reply.created';
    }
}

