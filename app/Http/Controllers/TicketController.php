<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Ticket::with(['customer', 'assignee'])->latest();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $tickets = $query->paginate(15);
        $types = ['aktivasi', 'gangguan', 'dismantle'];
        $currentType = $request->type ?? 'all';

        return view('tickets.index', compact('tickets', 'types', 'currentType'));
    }

    public function create(Request $request)
    {
        $customers = \App\Models\Customer::all();
        $users = \App\Models\User::all();
        $type = $request->type ?? 'gangguan';

        return view('tickets.create', compact('customers', 'users', 'type'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'type' => 'required|in:aktivasi,gangguan,dismantle',
            'status' => 'required|in:open,in_progress,resolved,closed,canceled',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket = \App\Models\Ticket::create($validated);

        // Notify targeted staff (Admin & Technicians)
        $staff = \App\Models\User::whereIn('role', ['administrator', 'admin', 'teknisi'])->get();
        \Illuminate\Support\Facades\Notification::send($staff, new \App\Notifications\TicketCreatedNotification($ticket));

        return redirect()->route('tickets.index', ['type' => $ticket->type])
            ->with('success', 'Ticket created successfully.');
    }

    public function show(\App\Models\Ticket $ticket)
    {
        $ticket->load(['customer', 'assignee', 'replies.user']);
        return view('tickets.show', compact('ticket'));
    }

    public function edit(\App\Models\Ticket $ticket)
    {
        $customers = \App\Models\Customer::all();
        $users = \App\Models\User::all();

        return view('tickets.edit', compact('ticket', 'customers', 'users'));
    }

    public function update(Request $request, \App\Models\Ticket $ticket)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'type' => 'required|in:aktivasi,gangguan,dismantle',
            'status' => 'required|in:open,in_progress,resolved,closed,canceled',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'resolution_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // 8.1. Prevent manual closing of Activation/Dismantle tickets
        if (in_array($ticket->type, ['aktivasi', 'dismantle']) && in_array($request->status, ['closed', 'resolved'])) {
            $wizardType = ($ticket->type === 'aktivasi') ? 'Aktivasi' : 'Dismantle';
            return redirect()->back()
                ->with('error', "Tiket $wizardType hanya dapat diselesaikan melalui Wizard $wizardType Pelanggan agar integritas data Billing & Inventory terjaga.");
        }

        $ticket->update($validated);

        // Sync with Customer Status (Expert Logic)
        if ($ticket->customer) {
            $customer = $ticket->customer;
            
            if ($ticket->status === 'closed' || $ticket->status === 'resolved') {
                if ($ticket->type === 'aktivasi') {
                    $customer->update([
                        'status' => \App\Models\Customer::STATUS_ACTIVE,
                        'is_active' => true
                    ]);
                } elseif ($ticket->type === 'dismantle') {
                    $customer->update([
                        'status' => \App\Models\Customer::STATUS_DISMANTLED,
                        'is_active' => false
                    ]);
                }
            } elseif ($ticket->status === 'canceled') {
                if ($ticket->type === 'aktivasi') {
                    $customer->update(['status' => \App\Models\Customer::STATUS_CANCELED]);
                } elseif ($ticket->type === 'dismantle') {
                    // If dismantle is canceled, customer probably stays active
                    $customer->update(['status' => \App\Models\Customer::STATUS_ACTIVE]);
                }
            }
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }

    public function destroy(\App\Models\Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket deleted successfully.');
    }

    public function claim(\App\Models\Ticket $ticket)
    {
        if ($ticket->assigned_to) {
            return back()->with('error', 'Tiket ini sudah diambil oleh teknisi lain.');
        }

        $ticket->update([
            'assigned_to' => auth()->id(),
            'status' => 'in_progress'
        ]);

        return back()->with('success', 'Tiket berhasil Anda klaim dan status berubah menjadi In Progress.');
    }

    public function reply(Request $request, \App\Models\Ticket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/attachments', 'public');
        }

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'attachment' => $attachmentPath
        ]);

        return back()->with('success', 'Balasan berhasil dikirim.');
    }
}
