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

        // Online users
        $onlineUsers = RadAcct::whereNull('acctstoptime')->count();

        // Recent activity logs
        $recentActivities = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user_name' => $log->user ? $log->user->name : 'System',
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'customers' => [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
                'suspended' => $suspendedCustomers,
                'new' => $newCustomers,
                'waiting_activation' => $waitingActivation,
            ],
            'invoices' => [
                'total_this_month' => $totalInvoices,
                'paid' => $paidInvoices,
                'unpaid' => $unpaidInvoices,
                'overdue' => $overdueInvoices,
            ],
            'revenue' => [
                'this_month' => (float) $monthlyRevenue,
            ],
            'tickets' => [
                'open' => $openTickets,
                'in_progress' => $inProgressTickets,
                'closed_this_month' => $closedTicketsThisMonth,
            ],
            'online_users' => $onlineUsers,
            'recent_activities' => $recentActivities,
        ]);
    }
}
