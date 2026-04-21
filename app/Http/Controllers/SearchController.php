<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function apiSearch(Request $request)
    {
        $query = $request->get('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::where('name', 'LIKE', "%{$query}%")
            ->orWhere('username', 'LIKE', "%{$query}%")
            ->orWhere('customer_code', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'username', 'customer_code']);

        $invoices = Invoice::where('invoice_number', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'invoice_number']);

        $tickets = Ticket::where('subject', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'subject']);

        $results = [];

        foreach ($customers as $c) {
            $results[] = [
                'type' => 'Subscriber',
                'title' => $c->name,
                'subtitle' => "{$c->username} ({$c->customer_code})",
                'url' => route('customers.show', $c->id),
                'icon' => 'user'
            ];
        }

        foreach ($invoices as $i) {
            $results[] = [
                'type' => 'Invoice',
                'title' => $i->invoice_number,
                'subtitle' => 'Billing Statement',
                'url' => route('invoices.show', $i->id),
                'icon' => 'bill'
            ];
        }

        foreach ($tickets as $t) {
            $results[] = [
                'type' => 'Ticket',
                'title' => $t->subject,
                'subtitle' => 'Support Ticket',
                'url' => route('tickets.show', $t->id),
                'icon' => 'ticket'
            ];
        }

        return response()->json($results);
    }
}
