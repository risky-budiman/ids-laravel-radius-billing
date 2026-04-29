<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    public function index()
    {
        // Only admins can see the partner list
        abort_unless(auth()->user()->isAdmin(), 403);

        $partners = User::where('role', User::ROLE_MITRA)
            ->withCount('customer as customers_count')
            ->latest()
            ->paginate(10);

        return view('partners.index', compact('partners'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return view('partners.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'commission_rate' => 'required|numeric|min:0',
            'commission_type' => 'required|in:percentage,fixed',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_MITRA,
            'commission_rate' => $request->commission_rate,
            'commission_type' => $request->commission_type,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name' => $request->bank_account_name,
            'is_active' => true,
        ]);

        return redirect()->route('partners.index')->with('success', 'Partner account created successfully.');
    }

    public function show(User $partner)
    {
        // Allow admin or the partner themselves
        abort_unless(auth()->user()->isAdmin() || auth()->id() === $partner->id, 403);
        abort_unless($partner->role === User::ROLE_MITRA, 404);

        $customers = \App\Models\Customer::where('partner_id', $partner->id)->with('package')->get();
        
        $commissions = PartnerCommission::where('partner_id', $partner->id)
            ->with(['customer', 'invoice'])
            ->latest()
            ->limit(50)
            ->get();

        $stats = [
            'total_earned' => PartnerCommission::where('partner_id', $partner->id)->sum('amount'),
            'balance' => PartnerCommission::where('partner_id', $partner->id)->where('status', 'earned')->sum('amount'),
            'total_withdrawn' => PartnerWithdrawal::where('partner_id', $partner->id)->where('status', 'paid')->sum('amount'),
            'customer_count' => $customers->count(),
        ];

        return view('partners.show', compact('partner', 'customers', 'commissions', 'stats'));
    }

    public function edit(User $partner)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($partner->role === User::ROLE_MITRA, 404);

        return view('partners.edit', compact('partner'));
    }

    public function update(Request $request, User $partner)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($partner->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'commission_rate' => 'required|numeric|min:0',
            'commission_type' => 'required|in:percentage,fixed',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'commission_rate' => $request->commission_rate,
            'commission_type' => $request->commission_type,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name' => $request->bank_account_name,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $partner->update($data);

        return redirect()->route('partners.index')->with('success', 'Partner account updated successfully.');
    }

    public function withdrawals()
    {
        $query = PartnerWithdrawal::with('partner');

        if (!auth()->user()->isAdmin()) {
            $query->where('partner_id', auth()->id());
        }

        $withdrawals = $query->latest()->paginate(10);

        return view('partners.withdrawals', compact('withdrawals'));
    }

    public function requestWithdrawal(Request $request)
    {
        $partner = auth()->user();
        abort_unless($partner->isMitra(), 403);

        $balance = PartnerCommission::where('partner_id', $partner->id)
            ->where('status', 'earned')
            ->sum('amount');

        $request->validate([
            'amount' => 'required|numeric|min:10000|max:' . $balance,
        ]);

        DB::transaction(function() use ($partner, $request) {
            $withdrawal = PartnerWithdrawal::create([
                'partner_id' => $partner->id,
                'amount' => $request->amount,
                'request_date' => now(),
                'status' => 'pending',
                'bank_name' => $partner->bank_name,
                'bank_account_number' => $partner->bank_account_number,
                'bank_account_name' => $partner->bank_account_name,
            ]);

            // We don't mark commissions as withdrawn yet, only when paid.
            // Or we could "lock" them. For simplicity, we'll just check balance.
        });

        return back()->with('success', 'Withdrawal request submitted successfully.');
    }

    public function processWithdrawal(PartnerWithdrawal $withdrawal, Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'status' => 'required|in:approved,paid,rejected',
            'bank_account_id' => 'required_if:status,paid|exists:bank_accounts,id',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function() use ($withdrawal, $request) {
            $withdrawal->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'processed_by' => auth()->id(),
                'payment_date' => $request->status === 'paid' ? now() : null,
            ]);

            if ($request->status === 'paid') {
                // 1. Mark linked commissions as withdrawn
                // This is a bit tricky if we allow partial withdrawals.
                // For now, we'll just take enough commissions to cover the amount.
                $remaining = $withdrawal->amount;
                $commissions = PartnerCommission::where('partner_id', $withdrawal->partner_id)
                    ->where('status', 'earned')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($commissions as $comm) {
                    if ($remaining <= 0) break;
                    
                    $comm->update([
                        'status' => 'withdrawn',
                        'withdrawal_id' => $withdrawal->id
                    ]);
                    $remaining -= $comm->amount;
                }

                // 2. Accounting Journal
                $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
                $journal = app(\App\Services\AccountingService::class)->recordPartnerWithdrawal($withdrawal, $bankAccount);
                
                if ($journal) {
                    $withdrawal->update(['journal_id' => $journal->id]);
                }
            }
        });

        return back()->with('success', 'Withdrawal status updated.');
    }
}
