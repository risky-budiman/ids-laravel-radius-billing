<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * List bank accounts with total liquid balance.
     */
    public function accounts()
    {
        $accounts = BankAccount::where('is_active', true)->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'bank_name' => $a->bank_name,
                'account_name' => $a->account_name,
                'account_number' => $a->account_number,
                'type' => $a->type,
                'balance' => (float) $a->balance,
                'description' => $a->description,
            ];
        });

        $totalBalance = $accounts->sum('balance');

        return response()->json([
            'total_balance' => $totalBalance,
            'accounts' => $accounts,
        ]);
    }

    /**
     * List transactions.
     */
    public function transactions(Request $request)
    {
        $query = BankTransaction::with(['bankAccount', 'creator'])->latest('transaction_date');

        if ($request->filled('bank_account_id') && $request->input('bank_account_id') !== 'all') {
            $query->where('bank_account_id', $request->input('bank_account_id'));
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $type = strtolower($request->input('type'));
            if ($type === 'credit' || $type === 'deposit') {
                $query->whereIn('type', ['credit', 'deposit']);
            } elseif ($type === 'debit' || $type === 'withdrawal') {
                $query->whereIn('type', ['debit', 'withdrawal']);
            } else {
                $query->where('type', $type);
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        $paginator = $query->paginate(20);

        $data = $paginator->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'bank_name' => $t->bankAccount ? $t->bankAccount->bank_name : '-',
                'account_name' => $t->bankAccount ? $t->bankAccount->account_name : '-',
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'reference_number' => $t->reference_number,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date ? $t->transaction_date->toIso8601String() : null,
                'creator_name' => $t->creator ? $t->creator->name : 'System',
            ];
        });

        return response()->json([
            'transactions' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Create new bank/cash account.
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'type' => 'required|in:bank,cash,payment_gateway',
            'balance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $account = BankAccount::create([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'] ?? null,
            'type' => $validated['type'],
            'balance' => $validated['balance'] ?? 0,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Rekening berhasil ditambahkan',
            'account' => $account,
        ], 201);
    }

    /**
     * Record single expense or income.
     */
    public function recordTransaction(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => 'required|in:debit,credit,deposit,withdrawal',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        $account = BankAccount::findOrFail($validated['bank_account_id']);
        $rawType = strtolower($validated['type']);
        $dbType = ($rawType === 'credit' || $rawType === 'deposit') ? 'deposit' : 'withdrawal';

        $trx = DB::transaction(function () use ($validated, $request, $account, $dbType) {
            $t = BankTransaction::create([
                'bank_account_id' => $account->id,
                'type' => $dbType,
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'transaction_date' => $validated['transaction_date'] ?? now(),
                'status' => 'completed',
                'created_by' => $request->user()->id,
                'reference_number' => 'TRX-' . strtoupper(uniqid()),
            ]);

            if ($dbType === 'deposit') {
                $account->increment('balance', $validated['amount']);
            } else {
                $account->decrement('balance', $validated['amount']);
            }

            return $t;
        });

        $typeLabel = $dbType === 'deposit' ? 'Pemasukan' : 'Pengeluaran';

        return response()->json([
            'message' => "Transaksi {$typeLabel} sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " berhasil dicatat.",
            'transaction_id' => $trx->id,
        ], 201);
    }

    /**
     * Record transfer between bank accounts.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:bank_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $from = BankAccount::findOrFail($validated['from_account_id']);
        $to = BankAccount::findOrFail($validated['to_account_id']);

        if ($from->balance < $validated['amount']) {
            return response()->json([
                'message' => 'Saldo rekening asal tidak mencukupi.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $request, $from, $to) {
            $ref = 'TRF-' . strtoupper(uniqid());

            // Debit from source
            $tOut = BankTransaction::create([
                'bank_account_id' => $from->id,
                'type' => 'debit',
                'amount' => $validated['amount'],
                'description' => "Transfer ke {$to->bank_name} ({$to->account_number}) " . ($validated['notes'] ? "— {$validated['notes']}" : ''),
                'transaction_date' => now(),
                'status' => 'completed',
                'created_by' => $request->user()->id,
                'reference_number' => $ref,
            ]);

            // Credit to destination
            $tIn = BankTransaction::create([
                'bank_account_id' => $to->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
                'description' => "Transfer dari {$from->bank_name} ({$from->account_number}) " . ($validated['notes'] ? "— {$validated['notes']}" : ''),
                'transaction_date' => now(),
                'status' => 'completed',
                'created_by' => $request->user()->id,
                'reference_number' => $ref,
                'related_transaction_id' => $tOut->id,
            ]);

            $tOut->update(['related_transaction_id' => $tIn->id]);
        });

        return response()->json([
            'message' => "Transfer sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " berhasil dilakukan.",
        ]);
    }
}
