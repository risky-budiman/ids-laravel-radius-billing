<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\TicketCreatedNotification;

class TicketController extends Controller
{
    /**
     * Display a listing of the customer's tickets.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        // Get tickets belonging to the currently logged in customer
        $tickets = Ticket::where('customer_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('customer-portal.tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        return view('customer-portal.tickets.create');
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $user = Auth::user();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        $ticket = Ticket::create([
            'customer_id' => $user->id,
            'type' => 'gangguan', // Default to gangguan for portal
            'status' => 'open',
            'priority' => 'medium', // Default priority
            'subject' => $request->subject,
            'description' => $request->description,
            'attachment' => $attachmentPath,
        ]);

        // Send notification to admins/technicians
        $staff = \App\Models\User::whereIn('role', ['administrator', 'admin', 'teknisi'])->get();
        foreach ($staff as $admin) {
            $admin->notify(new TicketCreatedNotification($ticket));
        }

        return redirect()->route('customer.tickets.index')->with('success', 'Tiket gangguan berhasil dikirim. Tim kami akan segera menindaklanjutinya.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        // Ensure customer can only view their own ticket
        if ($ticket->customer_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $ticket->load(['replies.user', 'assignee']);

        return view('customer-portal.tickets.show', compact('ticket'));
    }

    /**
     * Store a reply for the specified ticket.
     */
    public function reply(Request $request, Ticket $ticket)
    {
        // Ensure customer can only reply to their own ticket
        if ($ticket->customer_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }

        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket_replies', 'public');
        }

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null, // null indicates it's from the customer side
            'message' => $request->message,
            'attachment' => $attachmentPath,
        ]);

        // If the ticket was resolved/closed, reopen it since the customer replied
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'in_progress']);
        }

        return redirect()->route('customer.tickets.show', $ticket->id)->with('success', 'Balasan berhasil dikirim.');
    }
}
