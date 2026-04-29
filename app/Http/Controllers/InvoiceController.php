<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Services\PaymentGatewayService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;

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

        if ($request->filled('start_date')) {
            $query->whereDate('period_start', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('period_end', '<=', $request->get('end_date'));
        }

        $invoices = $query->paginate(10)->withQueryString();

        $bankAccounts = \App\Models\BankAccount::where('is_active', true)
            ->where('type', '!=', 'payment_gateway')
            ->get();

        $activeGateways = \App\Models\BankAccount::where('is_active', true)
            ->where('type', 'payment_gateway')
            ->get();

        return view('invoices.index', compact('invoices', 'activeGateways', 'bankAccounts'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::where('is_active', true)->get();
        $taxes = \App\Models\Tax::where('is_active', true)->get();
        return view('invoices.create', compact('customers', 'taxes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0', // This is interpreted as Subtotal
            'tax_id' => 'nullable|exists:taxes,id',
            'due_date' => 'required|date',
            'billing_period' => 'nullable|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $customer = \App\Models\Customer::find($validated['customer_id']);
        
        $subtotal = $validated['amount'];
        $taxAmount = 0;
        if ($request->filled('tax_id')) {
            $tax = \App\Models\Tax::find($request->tax_id);
            $taxAmount = ($subtotal * $tax->rate) / 100;
        }
        $totalAmount = $subtotal + $taxAmount;

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'billing_period' => $validated['billing_period'] ?? '1 Month',
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'customer_id' => $validated['customer_id'],
            'amount' => $totalAmount,
            'subtotal' => $subtotal,
            'tax_id' => $request->tax_id,
            'tax_amount' => $taxAmount,
            'status' => 'unpaid',
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
        ]);

        // Auto-Journal: Invoice Generated
        try {
            (new AccountingService())->recordInvoiceGenerated($invoice);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Auto-journal failed for BILL-{$invoice->invoice_number}: " . $e->getMessage());
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice generated successfully.');
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }
    
    public function edit(Invoice $invoice)
    {
        $customers = \App\Models\Customer::where('is_active', true)->get();
        return view('invoices.edit', compact('invoice', 'customers'));
    }

    // Editing not usually done, but marking as paid is a standard action
    public function update(Request $request, Invoice $invoice)
    {
        if ($request->has('mark_as_paid')) {
            try {
                $bankAccountId = $request->input('bank_account_id');
                app(\App\Services\InvoiceService::class)->markAsPaid($invoice, $bankAccountId);
                return redirect()->route('invoices.index')->with('success', 'Invoice marked as paid and customer reactivated.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error processing payment: ' . $e->getMessage());
            }
        } elseif ($request->has('cancel_payment')) {
            $invoice->update([
                'status' => 'unpaid',
                'paid_at' => null,
            ]);
            return redirect()->route('invoices.index')->with('success', 'Invoice payment cancelled (Reverted to unpaid).');
        }
        
        // General update
        $validated = $request->validate([
            'billing_period' => 'nullable|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);
        
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
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
