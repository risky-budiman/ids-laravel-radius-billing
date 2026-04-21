<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    // Auto-generate ticket number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $prefix = strtoupper(substr($ticket->type ?? 'GANGGUAN', 0, 3));
                $count = static::whereDate('created_at', now()->toDateString())->count();
                $ticket->ticket_number = $prefix . '-' . now()->format('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
