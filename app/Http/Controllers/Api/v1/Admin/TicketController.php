<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * List tickets with stats, filters, and pagination.
     */
    public function index(Request $request)
    {
        // 1. Stats query
        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'closed' => Ticket::whereIn('status', ['closed', 'resolved'])->count(),
            'gangguan' => Ticket::where('type', 'gangguan')->count(),
            'aktivasi' => Ticket::where('type', 'aktivasi')->count(),
            'dismantle' => Ticket::where('type', 'dismantle')->count(),
        ];

        // 2. Query tickets
        $query = Ticket::with(['customer', 'assignee'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'customer_id' => $t->customer_id,
                'customer_name' => $t->customer ? $t->customer->name : 'N/A',
                'customer_code' => $t->customer ? $t->customer->customer_code : '-',
                'type' => $t->type,
                'status' => $t->status,
                'priority' => $t->priority,
                'subject' => $t->subject,
                'description' => $t->description,
                'assigned_to_id' => $t->assigned_to,
                'assigned_to_name' => $t->assignee ? $t->assignee->name : 'Belum Ditugaskan',
                'is_overdue' => $t->isOverdue(),
                'created_at' => $t->created_at ? $t->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'stats' => $stats,
            'tickets' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Show ticket details with full reply thread.
     */
    public function show($id)
    {
        $ticket = Ticket::with(['customer.package', 'assignee', 'replies.user'])->findOrFail($id);

        $replies = $ticket->replies->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user_name' => $r->user ? $r->user->name : ($r->is_system ? 'System' : 'Customer'),
                'is_staff' => (bool) $r->user_id,
                'is_system' => (bool) $r->is_system,
                'message' => $r->message,
                'attachment_url' => $r->attachment ? asset('storage/' . $r->attachment) : null,
                'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'type' => $ticket->type,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'resolution_notes' => $ticket->resolution_notes,
                'is_overdue' => $ticket->isOverdue(),
                'assigned_to_id' => $ticket->assigned_to,
                'assigned_to_name' => $ticket->assignee ? $ticket->assignee->name : 'Belum Ditugaskan',
                'customer' => $ticket->customer ? [
                    'id' => $ticket->customer->id,
                    'name' => $ticket->customer->name,
                    'customer_code' => $ticket->customer->customer_code,
                    'username' => $ticket->customer->username,
                    'phone' => $ticket->customer->phone,
                    'address' => $ticket->customer->address,
                    'package_name' => $ticket->customer->package ? $ticket->customer->package->name : null,
                ] : null,
                'created_at' => $ticket->created_at ? $ticket->created_at->toIso8601String() : null,
            ],
            'replies' => $replies,
        ]);
    }

    /**
     * Store new ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|in:gangguan,aktivasi,dismantle,relokasi,maintenance',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket = Ticket::create(array_merge($validated, [
            'status' => 'open'
        ]));

        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
        ], 201);
    }

    /**
     * Update ticket.
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'type' => 'nullable|in:gangguan,aktivasi,dismantle,relokasi,maintenance',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:open,in_progress,resolved,closed,canceled',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'resolution_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(array_filter($validated, fn ($val) => !is_null($val)));

        return response()->json([
            'message' => 'Tiket berhasil diperbarui',
        ]);
    }

    /**
     * Claim ticket.
     */
    public function claim(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->assigned_to && $ticket->assigned_to !== $request->user()->id) {
            return response()->json([
                'message' => 'Tiket ini sudah diambil oleh petugas lain.',
            ], 422);
        }

        $ticket->update([
            'assigned_to' => $request->user()->id,
            'status' => 'in_progress',
        ]);

        return response()->json([
            'message' => 'Tiket berhasil diklaim',
        ]);
    }

    /**
     * Reply to ticket.
     */
    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'message' => 'required|string',
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Balasan berhasil dikirim',
            'reply_id' => $reply->id,
        ], 201);
    }

    /**
     * Close ticket.
     */
    public function close(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'closed',
            'resolution_notes' => $request->input('resolution_notes', 'Ditutup oleh ' . $request->user()->name),
        ]);

        return response()->json([
            'message' => "Tiket #{$ticket->ticket_number} berhasil ditutup.",
        ]);
    }

    /**
     * Delete ticket.
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'message' => 'Tiket berhasil dihapus',
        ]);
    }
}
