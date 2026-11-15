<?php

namespace App\Http\Controllers;

use App\Services\TicketService;
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

        // Calculate recap statistics for team performance
        $ticketStats = \App\Models\Ticket::select('type', 'status', \DB::raw('count(*) as count'))
            ->groupBy('type', 'status')
            ->get();

        $recap = [
            'aktivasi' => ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0, 'canceled' => 0, 'total' => 0, 'percentage' => 0],
            'gangguan' => ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0, 'canceled' => 0, 'total' => 0, 'percentage' => 0],
            'dismantle' => ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0, 'canceled' => 0, 'total' => 0, 'percentage' => 0],
            'overall' => ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0, 'canceled' => 0, 'total' => 0, 'percentage' => 0],
        ];

        foreach ($ticketStats as $stat) {
            $type = $stat->type;
            if (!in_array($type, ['aktivasi', 'gangguan', 'dismantle'])) {
                continue;
            }
            $status = $stat->status;
            $count = $stat->count;

            if (array_key_exists($status, $recap[$type])) {
                $recap[$type][$status] += $count;
                $recap['overall'][$status] += $count;
            }

            $recap[$type]['total'] += $count;
            $recap['overall']['total'] += $count;
        }

        // Calculate percentages based on resolved + closed (done) tickets
        foreach ($recap as $key => $data) {
            if ($data['total'] > 0) {
                $done = $data['resolved'] + $data['closed'];
                $recap[$key]['percentage'] = round(($done / $data['total']) * 100);
            }
        }

        return view('tickets.index', compact('tickets', 'types', 'currentType', 'recap'));
    }

    public function create(Request $request)
    {
        $customers = collect();   // pelanggan dicari lewat x-customer-picker (dulu seluruh pelanggan dimuat)
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

        // Notifikasi staf & WhatsApp grup dikirim oleh event model Ticket::created.
        $ticket = \App\Models\Ticket::create($validated);

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
        $customers = collect();   // pelanggan dicari lewat x-customer-picker (dulu seluruh pelanggan dimuat)
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

        // Tiket Aktivasi/Dismantle hanya bisa diselesaikan lewat wizard; pembatalan
        // menyinkronkan status pelanggan. Aturan yang sama dipakai API admin.
        try {
            app(TicketService::class)->update($ticket, $validated);
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }

    public function destroy(\App\Models\Ticket $ticket)
    {
        if (!auth()->user()->isAdministrator()) {
            abort(403, 'Unauthorized action. Only administrators can delete tickets.');
        }

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
