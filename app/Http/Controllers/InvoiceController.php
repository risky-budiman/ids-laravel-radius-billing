<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Services\PaymentGatewayService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer.package')->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->paginate(10)->withQueryString();

        $activeGateways = \App\Models\Gateway::where('type', 'payment')
            ->where('is_active', true)
            ->get();
            
        return view('invoices.index', compact('invoices', 'activeGateways'));
    }

    public function create()
    {
        // Simple manual invoice system for now
        $customers = \App\Models\Customer::where('is_active', true)->get();
        return view('invoices.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ]);
        
        $customer = \App\Models\Customer::find($validated['customer_id']);
        
        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'customer_id' => $validated['customer_id'],
            'amount' => $validated['amount'],
            'status' => 'unpaid',
            'due_date' => $validated['due_date'],
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice generated successfully.');
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }

    // Editing not usually done, but marking as paid is a standard action
    public function update(Request $request, Invoice $invoice)
    {
        if ($request->has('mark_as_paid')) {
            DB::transaction(function() use ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                $customer = $invoice->customer;
                if ($customer) {
                    // Reactivate if suspended
                    $customer->update([
                        'is_active' => true,
                        'status' => \App\Models\Customer::STATUS_ACTIVE
                    ]);

                    // For Renewal method, we sync dates ON payment
                    if ($customer->billing_method === 'renewal') {
                        $customer->syncBillingDates();
                    }
                }
            });
            return redirect()->route('invoices.index')->with('success', 'Invoice marked as paid and customer reactivated.');
        } elseif ($request->has('cancel_payment')) {
            $invoice->update([
                'status' => 'unpaid',
                'paid_at' => null,
            ]);
            return redirect()->route('invoices.index')->with('success', 'Invoice payment cancelled (Reverted to unpaid).');
        }
        
        return redirect()->route('invoices.index');
    }

    public function pay(Invoice $invoice, Request $request, PaymentGatewayService $paymentService)
    {
        try {
            $paymentUrl = $paymentService->createTransaction($invoice, $request->gateway);
            return redirect()->away($paymentUrl);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Payment Error: ' . $e->getMessage());
        }
    }

    public function sendWhatsApp(Invoice $invoice, WhatsAppService $waService)
    {
        $customer = $invoice->customer;
        
        if (!$customer || !$customer->phone) {
            return redirect()->back()->with('error', 'Nomor WhatsApp pelanggan tidak ditemukan.');
        }

        // 1. Generate Signed URL for the portal
        $portalUrl = URL::signedRoute('portal.invoice', ['invoice' => $invoice->id]);

        // 2. Compose Message
        $message = "Halo *{$customer->name}*,\n\n" .
                  "Tagihan internet Anda untuk nomor *{$invoice->invoice_number}* sebesar *Rp " . number_format($invoice->amount, 0, ',', '.') . "* telah terbit.\n\n" .
                  "Silakan bayar melalui link portal resmi kami berikut ini:\n" .
                  "{$portalUrl}\n\n" .
                  "Terima kasih.";

        // 3. Send via Service
        if ($waService->sendMessage($customer->phone, $message)) {
            return redirect()->back()->with('success', 'Link pembayaran telah dikirim ke WhatsApp pelanggan.');
        }

        return redirect()->back()->with('error', 'Gagal mengirim pesan WhatsApp. Pastikan Gateway Fonnte aktif.');
    }

    public function generateAutomated()
    {
        try {
            Artisan::call('app:process-billing');
            $output = Artisan::output();
            
            return redirect()->back()->with('success', 'Automated billing processed: ' . $output);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Billing Error: ' . $e->getMessage());
        }
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
}
