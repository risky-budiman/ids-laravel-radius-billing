<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Ticket;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Admin & Staff Ticket Monitoring Channel
Broadcast::channel('admin.tickets', function ($user) {
    return $user && ($user->isAdmin() || $user->isTeknisi() || $user->isAdministrator() || $user->isKasir());
});

// Realtime Single Ticket Channel (for live chat replies & status updates)
Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    if (!$user) return false;
    
    // Staff / Admin / Teknisi can access any ticket
    if ($user->isAdmin() || $user->isTeknisi() || $user->isAdministrator()) {
        return true;
    }
    
    // Customer can only access their own ticket
    $ticket = Ticket::find($ticketId);
    if (!$ticket) return false;

    return isset($user->customer_id) && (int) $user->customer_id === (int) $ticket->customer_id;
});
