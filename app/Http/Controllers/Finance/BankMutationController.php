<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankMutation;
use App\Services\BankMutationService;
use Illuminate\Http\Request;

/**
 * Mutasi bank masuk (Moota): yang cocok otomatis, dan yang menunggu dicocokkan manual.
 */
class BankMutationController extends Controller
{
    public function __construct(private BankMutationService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->input('status', 'unmatched');
        $query = BankMutation::with('invoice.customer', 'handler')->latest('id');
        if ($status !== 'all' && isset(BankMutation::STATUS_LABELS[$status])) {
            $query->where('status', $status);
        }

        return view('finance.mutations.index', [
            'mutations' => $query->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => BankMutation::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
            'unmatchedTotal' => (float) BankMutation::where('status', 'unmatched')->sum('amount'),
        ]);
    }

    public function match(Request $request, BankMutation $mutation)
    {
        $data = $request->validate(['invoice_number' => 'required|string|max:100'], [], ['invoice_number' => 'nomor invoice']);
        try {
            $invoice = $this->service->matchManually($mutation, $data['invoice_number'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} dilunasi dari mutasi ini.");
    }

    public function ignore(Request $request, BankMutation $mutation)
    {
        $data = $request->validate(['note' => 'nullable|string|max:300']);
        try {
            $this->service->ignore($mutation, $request->user(), $data['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mutasi ditandai diabaikan.');
    }
}
