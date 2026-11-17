<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Gangguan massal (OLT / port PON / ODP) dengan tiket induk dan pelanggan terdampak. */
class NetworkIncident extends Model
{
    public const SCOPES = ['olt' => 'OLT', 'port' => 'Port PON', 'odp' => 'ODP'];

    protected $fillable = ['scope_type', 'scope_id', 'scope_label', 'title', 'description', 'estimate', 'status', 'ticket_id',
        'notified_count', 'resolved_notified_count', 'started_at', 'resolved_at', 'resolution_notes', 'created_by', 'resolved_by'];

    protected $casts = ['started_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'network_incident_customer');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /** Tiket induk + tiket gangguan pelanggan yang tertaut. */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'incident_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
