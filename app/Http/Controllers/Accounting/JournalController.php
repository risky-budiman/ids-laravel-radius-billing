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
    public function index()
    {
        $journals = Journal::with(['items.account', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

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

            foreach ($request->items as $item) {
                if ($item['debit'] > 0 || $item['credit'] > 0) {
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'account_id' => $item['account_id'],
                        'debit' => $item['debit'] ?? 0,
                        'credit' => $item['credit'] ?? 0,
                    ]);
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
