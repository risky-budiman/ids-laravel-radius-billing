<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Tax;
use App\Models\BankAccount;
use App\Services\AccountingService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * List invoices with stats, filters, search, and pagination.
     */
    public function index(Request $request)
    {
        $currentMonth = now()->startOfMonth();

        // 1. Stats query
        $stats = [
            'total_this_month' => Invoice::where('created_at', '>=', $currentMonth)->count(),
            'paid_count' => Invoice::where('status', 'paid')->where('created_at', '>=', $currentMonth)->count(),
            'unpaid_count' => Invoice::where('status', 'unpaid')->count(),
            'overdue_count' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count(),
            'paid_sum' => (float) Invoice::where('status', 'paid')->where('paid_at', '>=', $currentMonth)->sum('amount'),
            'unpaid_sum' => (float) Invoice::where('status', 'unpaid')->sum('amount'),
        ];

        // 2. Query invoices
        $query = Invoice::with(['customer.package', 'tax'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'overdue') {
                $query->where('status', 'unpaid')->where('due_date', '<', now());
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('period')) {
            $periodParts = explode('-', $request->input('period'));
            if (count($periodParts) == 2) {
                $query->whereYear('created_at', $periodParts[0])
                      ->whereMonth('created_at', $periodParts[1]);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(function ($inv) {
            $isOverdue = $inv->status === 'unpaid' && $inv->due_date && $inv->due_date < now();
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_id' => $inv->customer_id,
                'customer_name' => $inv->customer ? $inv->customer->name : 'N/A',
                'customer_code' => $inv->customer ? $inv->customer->customer_code : '-',
                'package_name' => $inv->customer && $inv->customer->package ? $inv->customer->package->name : '-',
                'subtotal' => (float) ($inv->subtotal ?: $inv->amount),
                'tax_amount' => (float) ($inv->tax_amount ?: 0),
                'amount' => (float) $inv->amount,
                'status' => $inv->status,
                'is_overdue' => $isOverdue,
                'billing_period' => $inv->billing_period,
                'period_start' => $inv->period_start ? $inv->period_start->format('Y-m-d') : null,
                'period_end' => $inv->period_end ? $inv->period_end->format('Y-m-d') : null,
                'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                'paid_at' => $inv->paid_at ? $inv->paid_at->toIso8601String() : null,
                'payment_method' => $inv->payment_method,
                'payment_url' => $inv->payment_url,
                'created_at' => $inv->created_at ? $inv->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'stats' => $stats,
            'invoices' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Show single invoice detail.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['customer.package', 'tax'])->findOrFail($id);
        $isOverdue = $invoice->status === 'unpaid' && $invoice->due_date && $invoice->due_date < now();

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'subtotal' => (float) ($invoice->subtotal ?: $invoice->amount),
                'tax_id' => $invoice->tax_id,
                'tax_name' => $invoice->tax ? $invoice->tax->name : null,
                'tax_rate' => $invoice->tax ? (float) $invoice->tax->rate : 0,
                'tax_amount' => (float) ($invoice->tax_amount ?: 0),
                'amount' => (float) $invoice->amount,
                'status' => $invoice->status,
                'is_overdue' => $isOverdue,
                'billing_period' => $invoice->billing_period,
                'period_start' => $invoice->period_start ? $invoice->period_start->format('Y-m-d') : null,
                'period_end' => $invoice->period_end ? $invoice->period_end->format('Y-m-d') : null,
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                'paid_at' => $invoice->paid_at ? $invoice->paid_at->toIso8601String() : null,
                'payment_method' => $invoice->payment_method,
                'payment_url' => $invoice->payment_url,
                'payment_token' => $invoice->payment_token,
                'notes' => $invoice->notes,
                'customer' => $invoice->customer ? [
                    'id' => $invoice->customer->id,
                    'name' => $invoice->customer->name,
                    'customer_code' => $invoice->customer->customer_code,
                    'username' => $invoice->customer->username,
                    'phone' => $invoice->customer->phone,
                    'email' => $invoice->customer->email,
                    'address' => $invoice->customer->address,
                    'package_name' => $invoice->customer->package ? $invoice->customer->package->name : null,
                    'package_speed' => $invoice->customer->package ? $invoice->customer->package->download_speed : null,
                ] : null,
                'created_at' => $invoice->created_at ? $invoice->created_at->toIso8601String() : null,
            ]
        ]);
    }

    /**
     * Create manual invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'due_date' => 'required|date',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'billing_period' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $subtotal = $validated['amount'];
        $taxAmount = 0;
        $taxId = $validated['tax_id'] ?? null;

        if ($taxId) {
            $tax = Tax::find($taxId);
            if ($tax) {
                $taxAmount = ($subtotal * $tax->rate) / 100;
            }
        }

        $totalAmount = $subtotal + $taxAmount;

        $invoice = DB::transaction(function () use ($validated, $subtotal, $taxAmount, $totalAmount, $taxId) {
            $count = Invoice::whereDate('created_at', now()->toDateString())->count();
            $invoiceNumber = 'INV/' . now()->format('Ymd') . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'tax_id' => $taxId,
                'tax_amount' => $taxAmount,
                'amount' => $totalAmount,
                'status' => 'unpaid',
                'due_date' => $validated['due_date'],
                'period_start' => $validated['period_start'] ?? now()->startOfMonth(),
                'period_end' => $validated['period_end'] ?? now()->endOfMonth(),
                'billing_period' => $validated['billing_period'] ?? now()->format('F Y'),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Accrual Journal Entry
            try {
                app(AccountingService::class)->recordInvoiceAccrual($invoice);
            } catch (\Throwable $e) {
                Log::warning('Failed auto-journal for invoice: ' . $e->getMessage());
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'Tagihan berhasil dibuat',
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ], 201);
    }

    /**
     * Mark single invoice as paid.
     */
    public function pay(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'message' => 'Tagihan ini sudah berstatus Lunas.',
            ], 422);
        }

        $paymentMethod = $request->input('payment_method', 'cash');

        DB::transaction(function () use ($invoice, $paymentMethod) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
            ]);

            // Activate customer if suspended
            $customer = $invoice->customer;
            if ($customer) {
                if (!$customer->is_active || $customer->status === Customer::STATUS_SUSPENDED) {
                    $customer->update([
                        'is_active' => true,
                        'status' => Customer::STATUS_ACTIVE,
                    ]);
                }
                $customer->syncBillingDates();
            }

            // Record in accounting
            try {
                $bankAccount = BankAccount::where('is_active', true)->first();
                if ($bankAccount) {
                    app(AccountingService::class)->recordInvoicePayment($invoice, $bankAccount);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed auto-journal for payment: ' . $e->getMessage());
            }
        });

        return response()->json([
            'message' => "Tagihan #{$invoice->invoice_number} berhasil ditandai Lunas.",
        ]);
    }

    /**
     * Bulk Mark Paid.
     */
    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
            'payment_method' => 'nullable|string',
        ]);

        $ids = $request->input('ids');
        $paymentMethod = $request->input('payment_method', 'cash');
        $invoices = Invoice::whereIn('id', $ids)->where('status', '!=', 'paid')->get();
        $count = 0;
        $bankAccount = BankAccount::where('is_active', true)->first();

        foreach ($invoices as $invoice) {
            DB::transaction(function () use ($invoice, $paymentMethod, $bankAccount, &$count) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $paymentMethod,
                ]);

                $customer = $invoice->customer;
                if ($customer) {
                    if (!$customer->is_active || $customer->status === Customer::STATUS_SUSPENDED) {
                        $customer->update([
                            'is_active' => true,
                            'status' => Customer::STATUS_ACTIVE,
                        ]);
                    }
                    $customer->syncBillingDates();
                }

                if ($bankAccount) {
                    try {
                        app(AccountingService::class)->recordInvoicePayment($invoice, $bankAccount);
                    } catch (\Throwable $e) {}
                }

                $count++;
            });
        }

        return response()->json([
            'message' => "$count tagihan berhasil ditandai Lunas.",
        ]);
    }

    /**
     * Delete invoice.
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json([
            'message' => 'Tagihan berhasil dihapus',
        ]);
    }

    /**
     * Bulk Delete Invoices.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        $ids = $request->input('ids');
        Invoice::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => count($ids) . ' tagihan berhasil dihapus.',
        ]);
    }

    /**
     * Send WhatsApp Invoice Reminder.
     */
    public function sendWhatsApp($id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);

        if (!$invoice->customer || !$invoice->customer->phone) {
            return response()->json([
                'message' => 'Pelanggan tidak memiliki nomor WhatsApp yang terdaftar.',
            ], 422);
        }

        try {
            app(WhatsAppService::class)->sendInvoiceNotification($invoice);
            return response()->json([
                'message' => "Notifikasi WhatsApp berhasil dikirim ke {$invoice->customer->phone}.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mengirim WhatsApp: ' . $e->getMessage(),
            ], 500);
        }
    }
}
