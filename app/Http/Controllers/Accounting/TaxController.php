<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::with('chartOfAccount')->get();
        $bankAccounts = \App\Models\BankAccount::where('is_active', true)->get();
        
        // Tax Application Mode Settings
        $taxMode = \App\Models\Setting::where('key', 'tax_mode')->first()?->value ?? 'individual';
        $taxRate = Tax::where('is_active', true)->first()?->rate ?? 11;

        // Date Filter for Tax Summary Report
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // 1. Tax Output (PPN Keluaran - Code 2103)
        $taxOutputAccount = ChartOfAccount::where('code', '2103')->first();
        $totalTaxOutput = 0;
        if ($taxOutputAccount) {
            $taxOutputItems = \App\Models\JournalItem::where('account_id', $taxOutputAccount->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })->get();
            $totalTaxOutput = $taxOutputItems->sum('credit') - $taxOutputItems->sum('debit');
        }

        // 2. Tax Input (PPN Masukan - Code 1106)
        $taxInputAccount = ChartOfAccount::where('code', '1106')->first() ?? ChartOfAccount::where('name', 'like', '%PPN Masukan%')->first();
        $totalTaxInput = 0;
        if ($taxInputAccount) {
            $taxInputItems = \App\Models\JournalItem::where('account_id', $taxInputAccount->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })->get();
            $totalTaxInput = $taxInputItems->sum('debit') - $taxInputItems->sum('credit');
        }

        $netTaxPayable = $totalTaxOutput - $totalTaxInput;

        // Total Net Tax Liability (Cumulative Saldo Akun 2103)
        $totalTaxLiability = 0;
        if ($taxOutputAccount) {
            $items = \App\Models\JournalItem::where('account_id', $taxOutputAccount->id)->get();
            $totalTaxLiability = max(0, $items->sum('credit') - $items->sum('debit'));
        }

        // Tax Payments History
        $taxPayments = \App\Models\BankTransaction::with('bankAccount')
            ->where('description', 'like', '[Setor Pajak Negara]%')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('accounting.taxes.index', compact(
            'taxes', 'bankAccounts', 'totalTaxLiability', 'taxPayments',
            'taxMode', 'taxRate', 'startDate', 'endDate', 'totalTaxOutput', 'totalTaxInput', 'netTaxPayable'
        ));
    }

    public function create()
    {
        $accounts = ChartOfAccount::whereIn('type', ['liability', 'asset'])->get();
        return view('accounting.taxes.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:taxes,code',
            'rate' => 'required|numeric|min:0|max:100',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        Tax::create($request->all());

        return redirect()->route('accounting.taxes.index')->with('success', 'Pajak berhasil ditambahkan.');
    }

    public function edit(Tax $tax)
    {
        $accounts = ChartOfAccount::whereIn('type', ['liability', 'asset'])->get();
        return view('accounting.taxes.edit', compact('tax', 'accounts'));
    }

    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:taxes,code,' . $tax->id,
            'rate' => 'required|numeric|min:0|max:100',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $tax->update($request->all());

        return redirect()->route('accounting.taxes.index')->with('success', 'Pajak berhasil diperbarui.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();
        return back()->with('success', 'Pajak berhasil dihapus.');
    }

    /**
     * Process tax payment to government (Kas Negara)
     */
    public function payTax(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        $bankAccount = \App\Models\BankAccount::findOrFail($request->bank_account_id);
        if ($bankAccount->balance < $request->amount) {
            return back()->with('error', 'Saldo rekening tidak mencukupi untuk pembayaran pajak ini.')->withInput();
        }

        $taxAccount = ChartOfAccount::where('code', '2103')->first() ?? ChartOfAccount::where('type', 'liability')->where('name', 'like', '%pajak%')->first();

        \Illuminate\Support\Facades\DB::transaction(function() use ($request, $bankAccount, $taxAccount) {
            \App\Models\BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'chart_of_account_id' => $taxAccount?->id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'description' => '[Setor Pajak Negara] ' . $request->description,
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->payment_date,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->back()->with('success', 'Setoran PPN ke Kas Negara berhasil diproses dan saldo hutang pajak telah berkurang.');
    }

    /**
     * Record Tax Input (Pajak Masukan - PPN Pembelian Supplier/Vendor)
     */
    public function recordTaxInput(Request $request)
    {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'tax_invoice_number' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        $taxInputAccount = ChartOfAccount::where('code', '1106')->first() ?? ChartOfAccount::where('name', 'like', '%PPN Masukan%')->first();
        if (!$taxInputAccount) {
            $taxInputAccount = ChartOfAccount::create([
                'code' => '1106',
                'name' => 'PPN Masukan',
                'type' => 'asset',
                'is_active' => true,
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($request, $taxInputAccount) {
            $journal = \App\Models\Journal::create([
                'date' => $request->transaction_date,
                'reference' => 'TAX-IN-' . time(),
                'description' => '[Pajak Masukan] ' . $request->vendor_name . ' - ' . $request->description,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit: PPN Masukan (Aset / Pengurang Hutang PPN)
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $taxInputAccount->id,
                'debit' => $request->amount,
                'credit' => 0,
            ]);

            // If bank account selected, credit Bank Transaction & Bank Coa
            if ($request->bank_account_id) {
                $bankAccount = \App\Models\BankAccount::findOrFail($request->bank_account_id);
                $bankTx = new \App\Models\BankTransaction([
                    'bank_account_id' => $bankAccount->id,
                    'chart_of_account_id' => $taxInputAccount->id,
                    'type' => 'withdrawal',
                    'amount' => $request->amount,
                    'description' => '[Pajak Masukan Vendor] ' . $request->vendor_name . ' (' . $request->description . ')',
                    'reference_number' => $request->tax_invoice_number,
                    'transaction_date' => $request->transaction_date,
                    'created_by' => auth()->id(),
                ]);
                $bankTx->skipAutoJournal = true;
                $bankTx->save();

                // Credit Bank Account in Journal (fallback to default bank account if not configured)
                $creditAccountId = $bankAccount->chart_of_account_id ?? 
                                   (ChartOfAccount::where('code', '1102')->first()?->id ?? 
                                    ChartOfAccount::where('type', 'asset')->where('name', 'like', '%Kas%')->orWhere('name', 'like', '%Bank%')->first()?->id);

                if ($creditAccountId) {
                    \App\Models\JournalItem::create([
                        'journal_id' => $journal->id,
                        'account_id' => $creditAccountId,
                        'debit' => 0,
                        'credit' => $request->amount,
                    ]);
                }
            } else {
                // Default Credit: Hutang Usaha / Modal
                $creditCoa = ChartOfAccount::where('type', 'liability')->first() ?? $taxInputAccount;
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $creditCoa->id,
                    'debit' => 0,
                    'credit' => $request->amount,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Pajak Masukan berhasil dicatat dan akan mengkreditkan/mengkoreksi hutang PPN Anda.');
    }
}
