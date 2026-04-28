<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Ticket extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'type',
        'status',
        'priority',
        'subject',
        'description',
        'attachment',
        'resolution_notes',
        'assigned_to',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Check if the ticket has exceeded the SLA (24 hours for open tickets)
     */
    public function isOverdue()
    {
        return in_array($this->status, ['open', 'in_progress']) && $this->created_at->diffInHours(now()) >= 24;
    }

    // Auto-generate ticket number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $type = $ticket->type ?? 'gangguan';
                $prefixKey = 'ticket_prefix_' . $type;
                
                $fallbacks = [
                    'gangguan' => 'TT',
                    'aktivasi' => 'AO',
                    'dismantle' => 'DO',
                    'relokasi' => 'RL',
                    'maintenance' => 'MT'
                ];
                
                $prefix = get_setting($prefixKey, $fallbacks[$type] ?? 'TKT');
                $count = static::whereDate('created_at', now()->toDateString())->count();
                $ticket->ticket_number = $prefix . '/' . now()->format('Ymd') . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
