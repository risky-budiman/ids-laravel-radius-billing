<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankTransactionController extends Controller
{
    public function transfer()
    {
        $accounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        return view('finance.bank-transactions.transfer', compact('accounts'));
    }

    public function processTransfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:bank_accounts,id',
            'to_account_id' => 'required|exists:bank_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $fromAccount = BankAccount::find($request->from_account_id);
        
        if ($fromAccount->balance < $request->amount) {
            return back()->with('error', 'Saldo tidak mencukupi untuk melakukan transfer ini.')->withInput();
        }

        DB::transaction(function () use ($request) {
            // Withdrawal from source
            $withdrawal = BankTransaction::create([
                'bank_account_id' => $request->from_account_id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'description' => 'Transfer Keluar: ' . ($request->description ?: 'Tanpa keterangan'),
                'transaction_date' => $request->transaction_date,
                'created_by' => auth()->id(),
            ]);

            // Deposit to destination
            $deposit = BankTransaction::create([
                'bank_account_id' => $request->to_account_id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'description' => 'Transfer Masuk: ' . ($request->description ?: 'Tanpa keterangan'),
                'transaction_date' => $request->transaction_date,
                'related_transaction_id' => $withdrawal->id,
                'created_by' => auth()->id(),
            ]);

            // Link back withdrawal to deposit
            $withdrawal->update(['related_transaction_id' => $deposit->id]);
        });

        return redirect()->route('bank-accounts.index')->with('success', 'Transfer saldo berhasil diproses.');
    }

    public function expense()
    {
        $accounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $categories = ChartOfAccount::where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        return view('finance.bank-transactions.expense', compact('accounts', 'categories'));
    }

    public function processExpense(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
        ]);

        $account = BankAccount::find($request->bank_account_id);
        
        if ($account->balance < $request->amount) {
            return back()->with('error', 'Saldo tidak mencukupi untuk pengeluaran ini.')->withInput();
        }

        BankTransaction::create([
            'bank_account_id' => $request->bank_account_id,
            'chart_of_account_id' => $request->chart_of_account_id,
            'type' => 'withdrawal',
            'amount' => $request->amount,
            'description' => 'Pengeluaran: ' . $request->description,
            'reference_number' => $request->reference_number,
            'transaction_date' => $request->transaction_date,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('bank-accounts.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function depositToCompany()
    {
        $user = auth()->user();
        // Kasir only sees their own accounts
        $myAccounts = BankAccount::where('user_id', $user->id)->where('is_active', true)->get();
        // Company accounts (where user_id is null)
        $companyAccounts = BankAccount::whereNull('user_id')->where('is_active', true)->get();
        
        return view('finance.bank-transactions.cashier-deposit', compact('myAccounts', 'companyAccounts'));
    }

    public function processDepositToCompany(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:bank_accounts,id',
            'to_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $fromAccount = BankAccount::find($request->from_account_id);
        
        // Ensure the account belongs to the user
        if ($fromAccount->user_id !== auth()->id()) {
            abort(403);
        }

        if ($fromAccount->balance < $request->amount) {
            return back()->with('error', 'Saldo kasir tidak mencukupi untuk setoran ini.')->withInput();
        }

        DB::transaction(function () use ($request) {
            // Withdrawal from Kasir
            $withdrawal = BankTransaction::create([
                'bank_account_id' => $request->from_account_id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'description' => 'Setoran Kasir ke Perusahaan: ' . ($request->description ?: 'Setoran rutin'),
                'transaction_date' => $request->transaction_date,
                'created_by' => auth()->id(),
            ]);

            // Deposit to Company
            BankTransaction::create([
                'bank_account_id' => $request->to_account_id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'description' => 'Terima Setoran dari Kasir: ' . auth()->user()->name,
                'transaction_date' => $request->transaction_date,
                'related_transaction_id' => $withdrawal->id,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('bank-accounts.index')->with('success', 'Setoran dana ke perusahaan berhasil diproses.');
    }

    public function income()
    {
        $user = auth()->user();
        $query = BankAccount::where('is_active', true);
        
        if (!$user->isAdministrator()) {
            $query->where('user_id', $user->id);
        }

        $accounts = $query->orderBy('bank_name')->get();
        $categories = ChartOfAccount::whereIn('type', ['income', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        
        return view('finance.bank-transactions.income', compact('accounts', 'categories'));
    }

    public function processIncome(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
        ]);

        $account = BankAccount::find($request->bank_account_id);
        
        // Safety check for non-admins
        if (!auth()->user()->isAdministrator() && $account->user_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($request) {
            BankTransaction::create([
                'bank_account_id' => $request->bank_account_id,
                'chart_of_account_id' => $request->chart_of_account_id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'description' => '[Pemasukan] ' . $request->description,
                'transaction_date' => $request->transaction_date,
                'reference_number' => $request->reference_number,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('bank-accounts.index')->with('success', 'Pemasukan berhasil dicatat.');
    }

    public function destroy(BankTransaction $bankTransaction)
    {
        // Strict Authorization: Only Administrator
        if (!auth()->user()->isAdministrator()) {
            abort(403, 'Hanya Administrator yang diperbolehkan menghapus transaksi.');
        }

        $accountName = $bankTransaction->bankAccount->bank_name;
        
        DB::transaction(function () use ($bankTransaction) {
            // If it has a related transaction (Transfer/Deposit), delete that too
            if ($bankTransaction->related_transaction_id) {
                $related = BankTransaction::find($bankTransaction->related_transaction_id);
                if ($related) {
                    $related->delete();
                }
            }
            
            $bankTransaction->delete();
        });

        return back()->with('success', "Transaksi pada rekening {$accountName} berhasil dihapus dan saldo telah dikoreksi.");
    }
}
