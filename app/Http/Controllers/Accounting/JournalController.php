<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $query = Journal::with(['items.account', 'creator']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('items.account', function($aq) use ($search) {
                      $aq->where('code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $journals = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.journals.index', compact('journals'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
        ]);

        if (is_accounting_locked($request->date)) {
            return back()->withErrors(['date' => 'Tanggal ini berada dalam periode yang sudah dikunci (Tutup Buku).'])->withInput();
        }

        $totalDebit = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['balance' => 'Total Debit must equal Total Credit.'])->withInput();
        }

        DB::transaction(function () use ($request) {
            $journal = Journal::create([
                'date' => $request->date,
                'reference' => $this->generateReference($request->date),
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            // Map CoA to Bank Accounts to check if any item hits a bank
            $accountIds = collect($request->items)->pluck('account_id')->unique();
            $bankAccounts = \App\Models\BankAccount::whereIn('chart_of_account_id', $accountIds)->get()->keyBy('chart_of_account_id');

            foreach ($request->items as $item) {
                if ($item['debit'] > 0 || $item['credit'] > 0) {
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'account_id' => $item['account_id'],
                        'debit' => $item['debit'] ?? 0,
                        'credit' => $item['credit'] ?? 0,
                    ]);

                    // If this account is a bank account, record a bank transaction
                    if (isset($bankAccounts[$item['account_id']])) {
                        $bankAccount = $bankAccounts[$item['account_id']];
                        $amount = ($item['debit'] > 0) ? $item['debit'] : $item['credit'];
                        $type = ($item['debit'] > 0) ? 'deposit' : 'withdrawal';

                        $bankTx = new \App\Models\BankTransaction();
                        $bankTx->bank_account_id = $bankAccount->id;
                        $bankTx->chart_of_account_id = $item['account_id']; // This is optional as it's the bank's CoA
                        $bankTx->type = $type;
                        $bankTx->amount = $amount;
                        $bankTx->reference_number = $journal->reference;
                        $bankTx->description = $request->description;
                        $bankTx->transaction_date = $request->date;
                        $bankTx->status = 'completed';
                        $bankTx->created_by = auth()->id();
                        
                        // IMPORTANT: Skip auto-journal because we are already in a journal creation process
                        $bankTx->skipAutoJournal = true;
                        $bankTx->save();
                    }
                }
            }
        });

        return redirect()->route('accounting.journals.index')->with('success', 'Journal entry recorded successfully.');
    }

    public function show(Journal $journal)
    {
        $journal->load(['items.account', 'creator']);
        return view('accounting.journals.show', compact('journal'));
    }

    public function destroy(Journal $journal)
    {
        if (is_accounting_locked($journal->date)) {
            return back()->with('error', 'Gagal: Transaksi pada periode yang sudah dikunci (Tutup Buku) tidak dapat dihapus.');
        }

        DB::transaction(function () use ($journal) {
            // If this journal is linked to a bank transaction, delete that too
            // Note: We use reference to find linked TRX
            if (strpos($journal->reference, 'JV-') !== 0) {
                // If it's not a Manual Journal (JV), it might be an auto-journal from TRX or BILL
                // We check if it matches TRX-ID
                if (preg_match('/^TRX-(\d+)$/', $journal->reference, $matches)) {
                    $trxId = $matches[1];
                    \App\Models\BankTransaction::where('id', $trxId)->delete();
                }
            }

            // Also check if any manual journal items hit bank accounts
            // If so, deleting the journal should ideally delete the BankTransaction we created in store()
            \App\Models\BankTransaction::where('reference_number', $journal->reference)->delete();

            $journal->delete();
        });

        return redirect()->route('accounting.journals.index')->with('success', 'Jurnal berhasil dihapus.');
    }

    private function generateReference($date)
    {
        $prefix = 'JV-' . date('Ym', strtotime($date)) . '-';
        $lastJournal = Journal::where('reference', 'like', $prefix . '%')
            ->orderBy('reference', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->reference, -4);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
