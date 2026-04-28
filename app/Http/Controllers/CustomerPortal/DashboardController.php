<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Radius\RadAcct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get the currently authenticated customer from the customer guard
     */
    private function getCustomer()
    {
        $customer = Auth::guard('customer')->user();
        
        // If not logged in as customer, maybe logged in as admin? 
        // Fallback for admin preview
        if (!$customer && Auth::guard('web')->check()) {
            // This is just for admin to view the portal. 
            // In a real scenario, admin might need to specify which customer to view.
            // For now, we'll just redirect to home or handle gracefully.
            return null;
        }

        return $customer;
    }

    public function index()
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            return redirect()->route('customer.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Check Online Status from Radius
        $isOnline = RadAcct::where('username', $customer->username)
            ->whereNull('acctstoptime')
            ->exists();

        // Get Latest Session (if any)
        $session = RadAcct::where('username', $customer->username)
            ->orderBy('acctstarttime', 'desc')
            ->first();

        // Get Unpaid Invoices
        $unpaidInvoices = $customer->invoices()
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->get();

        return view('customer-portal.dashboard', compact('customer', 'isOnline', 'session', 'unpaidInvoices'));
    }

    public function invoices()
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            return redirect()->route('customer.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $invoices = $customer->invoices()
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('customer-portal.invoices', compact('customer', 'invoices'));
    }

    public function boosters()
    {
        $customer = $this->getCustomer();
        
        if (!$customer) {
            return redirect()->route('customer.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $boosters = \App\Models\Booster::where('is_active', true)->get();

        return view('customer-portal.boosters', compact('customer', 'boosters'));
    }

    public function buyBooster(Request $request, \App\Models\Booster $booster)
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            return back()->with('error', 'Silakan login terlebih dahulu.');
        }

        // Create Purchase Record
        $purchase = \App\Models\CustomerBooster::create([
            'customer_id' => $customer->id,
            'booster_id' => $booster->id,
            'amount_paid' => $booster->price,
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Reset RADIUS usage
        \App\Models\Radius\RadAcct::where('username', $customer->username)
            ->whereNull('acctstoptime')
            ->update([
                'acctinputoctets' => 0,
                'acctoutputoctets' => 0
            ]);

        try {
            $nas = \App\Models\Radius\Nas::first();
            if ($nas) {
                $coa = new \App\Services\RadiusCoAService();
                $coa->disconnect($nas->nasipaddress, $nas->secret, $customer->username);
            }
        } catch (\Exception $e) {
            \Log::warning("CoA failed after booster purchase: " . $e->getMessage());
        }

        return redirect()->route('customer.dashboard')->with('success', "Berhasil membeli Booster {$booster->name}!");
    }
}
