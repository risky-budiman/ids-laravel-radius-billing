<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = BankAccount::with('user')->orderBy('bank_name');

        // Privacy: Only administrators see everything
        if (!$user->isAdministrator()) {
            $query->where('user_id', $user->id);
        }

        $accounts = $query->get();
        
        // Total balance only for administrators
        $totalBalance = $user->isAdministrator() ? BankAccount::sum('balance') : 0;
        
        return view('finance.bank-accounts.index', compact('accounts', 'totalBalance'));
    }

    public function create()
    {
        $users = User::where('is_active', true)
            ->whereIn('role', ['admin', 'teknisi'])
            ->orderBy('name')
            ->get();
        return view('finance.bank-accounts.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'type' => 'required|in:bank,cash,e_wallet,payment_gateway',
            'user_id' => 'nullable|exists:users,id',
            'initial_balance' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $account = BankAccount::create([
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'type' => $request->type,
            'user_id' => $request->user_id,
            'balance' => 0, // Will be updated by transaction
            'description' => $request->description,
        ]);

        // Create initial deposit transaction if initial balance > 0
        if ($request->initial_balance > 0) {
            $account->transactions()->create([
                'type' => 'deposit',
                'amount' => $request->initial_balance,
                'description' => 'Saldo Awal',
                'transaction_date' => now(),
                'status' => 'completed',
            ]);
        }

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function show(BankAccount $bankAccount)
    {
        $user = auth()->user();
        
        // Authorization: Staff can only see their own accounts
        if (!$user->isAdministrator() && $bankAccount->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke rekening ini.');
        }

        $transactions = $bankAccount->transactions()->orderBy('transaction_date', 'desc')->paginate(20);
        
        return view('finance.bank-accounts.show', compact('bankAccount', 'transactions'));
    }

    public function edit(BankAccount $bankAccount)
    {
        $users = User::where('is_active', true)
            ->whereIn('role', ['admin', 'teknisi'])
            ->orderBy('name')
            ->get();
        return view('finance.bank-accounts.edit', compact('bankAccount', 'users'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'type' => 'required|in:bank,cash,e_wallet,payment_gateway',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $bankAccount->update($request->all());

        return redirect()->route('bank-accounts.index')->with('success', 'Data rekening berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Check if account has transactions other than initial balance
        // or just allow delete with cascade (already defined in migration)
        if ($bankAccount->transactions()->count() > 1) {
            return back()->with('error', 'Rekening yang sudah memiliki riwayat transaksi tidak dapat dihapus. Silakan nonaktifkan saja untuk menjaga integritas data.');
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening berhasil dihapus.');
    }
}
