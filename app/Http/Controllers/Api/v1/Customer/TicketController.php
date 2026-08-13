<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use App\Notifications\TicketCreatedNotification;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();
        $tickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->get();

        return response()->json([
            'tickets' => $tickets
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $customer = $request->user();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'type' => 'gangguan', // Default to gangguan for portal
            'status' => 'open',
            'priority' => 'medium', // Default priority
            'subject' => $request->subject,
            'description' => $request->description,
            'attachment' => $attachmentPath,
        ]);

        // Send notification to admins/technicians
        try {
            $staff = \App\Models\User::whereIn('role', ['administrator', 'admin', 'teknisi'])->get();
            foreach ($staff as $admin) {
                $admin->notify(new TicketCreatedNotification($ticket));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send TicketCreatedNotification for ticket #' . $ticket->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'ticket' => $ticket
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $customer = $request->user();
        $ticket = Ticket::where('customer_id', $customer->id)->findOrFail($id);

        $ticket->load(['replies.user', 'assignee']);

        return response()->json([
            'ticket' => $ticket
        ]);
    }

    public function reply(Request $request, $id)
    {
        $customer = $request->user();
        $ticket = Ticket::where('customer_id', $customer->id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'message' => 'Tiket sudah ditutup. Anda tidak dapat mengirim balasan lagi.'
            ], 400);
        }

        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket_replies', 'public');
        }

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null, // null indicates it's from the customer side
            'message' => $request->message,
            'attachment' => $attachmentPath,
        ]);

        // If the ticket was resolved/closed, reopen it since the customer replied
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json([
            'message' => 'Balasan berhasil dikirim',
            'reply' => $reply
        ]);
    }

    public function close(Request $request, $id)
    {
        $customer = $request->user();
        $ticket = Ticket::where('customer_id', $customer->id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'message' => 'Tiket sudah ditutup.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $ticket->update([
            'status' => 'closed'
        ]);

        $msg = "⚙️ Tiket ditutup oleh pelanggan. Alasan: \"{$request->reason}\"";
        
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'is_system' => true,
            'message' => $msg,
        ]);

        return response()->json([
            'message' => 'Tiket berhasil ditutup',
            'ticket' => $ticket
        ]);
    }
}
