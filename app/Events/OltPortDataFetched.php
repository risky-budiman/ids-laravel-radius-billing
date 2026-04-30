<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OltPortDataFetched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $oltId;
    public $portId;
    public $onus;

    /**
     * Create a new event instance.
     */
    public function __construct($oltId, $portId, $onus)
    {
        $this->oltId = $oltId;
        $this->portId = $portId;
        $this->onus = $onus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // We use a public channel for now to make testing easier, 
        // but it can be changed to PrivateChannel for security.
        return [
            new Channel('olt-port.' . $this->portId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'data.fetched';
    }
}
