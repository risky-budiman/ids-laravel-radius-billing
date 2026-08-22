<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Radius\RadAcct;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary statistics.
     */
    public function index(Request $request)
    {
        // Customer statistics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', Customer::STATUS_ACTIVE)->count();
        $suspendedCustomers = Customer::where('status', Customer::STATUS_SUSPENDED)->count();
        $newCustomers = Customer::where('status', Customer::STATUS_NEW)->count();
        $waitingActivation = Customer::where('status', Customer::STATUS_WAITING_ACTIVATION)->count();

        // Invoice statistics (current month)
        $currentMonth = now()->startOfMonth();
        $totalInvoices = Invoice::where('created_at', '>=', $currentMonth)->count();
        $paidInvoices = Invoice::where('status', 'paid')
            ->where('created_at', '>=', $currentMonth)->count();
        $unpaidInvoices = Invoice::where('status', 'unpaid')
            ->where('created_at', '>=', $currentMonth)->count();
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now())->count();

        // Revenue (current month)
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $currentMonth)
            ->sum('amount');

        // Ticket statistics
        $openTickets = Ticket::where('status', 'open')->count();
        $inProgressTickets = Ticket::where('status', 'in_progress')->count();
        $closedTicketsThisMonth = Ticket::where('status', 'closed')
            ->where('updated_at', '>=', $currentMonth)->count();

        // Online users (FreeRADIUS fallback)
        try {
            $onlineUsers = RadAcct::whereNull('acctstoptime')->count();
        } catch (\Exception $e) {
            $onlineUsers = 0;
        }

        // Recent activity logs fallback
        try {
            $recentActivities = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action ?? 'info',
                        'description' => $log->description ?? '-',
                        'user_name' => $log->user ? $log->user->name : 'System',
                        'created_at' => $log->created_at ? $log->created_at->toIso8601String() : now()->toIso8601String(),
                    ];
                });
        } catch (\Exception $e) {
            $recentActivities = [];
        }

        return response()->json([
            'customers' => [
                'total' => (int) $totalCustomers,
                'active' => (int) $activeCustomers,
                'suspended' => (int) $suspendedCustomers,
                'new' => (int) $newCustomers,
                'waiting_activation' => (int) $waitingActivation,
            ],
            'invoices' => [
                'total_this_month' => (int) $totalInvoices,
                'paid' => (int) $paidInvoices,
                'unpaid' => (int) $unpaidInvoices,
                'overdue' => (int) $overdueInvoices,
            ],
            'revenue' => [
                'this_month' => (float) $monthlyRevenue,
            ],
            'tickets' => [
                'open' => (int) $openTickets,
                'in_progress' => (int) $inProgressTickets,
                'closed_this_month' => (int) $closedTicketsThisMonth,
            ],
            'online_users' => (int) $onlineUsers,
            'recent_activities' => $recentActivities,
        ]);
    }
}
