<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Sales Ledger: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $sales->name }}</span>
            </h2>
            <a href="{{ route('sales-commissions.index') }}" class="text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: Profile & Actions -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                <div class="w-24 h-24 rounded-full bg-indigo-100 dark:bg-indigo-900 mx-auto flex items-center justify-center text-3xl font-black text-indigo-600 mb-4 shadow-inner">
                    {{ substr($sales->name, 0, 1) }}
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $sales->name }}</h3>
                <p class="text-sm text-gray-500 mb-6">{{ $sales->email }}</p>

                <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 mb-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Available Balance</p>
                    <h4 class="text-3xl font-black text-emerald-600">Rp {{ number_format($balance, 0, ',', '.') }}</h4>
                </div>

                <!-- Withdrawal Form -->
                <div class="text-left">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-widest border-b pb-2">Process Withdrawal</h4>
                    <form action="{{ route('sales-commissions.withdrawals.store', $sales) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="amount" :value="__('Withdrawal Amount (Rp)')" />
                            <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" placeholder="e.g. 50000" required />
                            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                        </div>

                        <div>
                            <x-input-label for="bank_account_id" :value="__('Pay From Account')" />
                            @php $bankAccounts = \App\Models\BankAccount::where('is_active', true)->get(); @endphp
                            <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">[{{ $ba->bank_name }}] {{ $ba->account_name }} - Rp {{ number_format($ba->balance, 0) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="reference_number" :value="__('Reference / Receipt #')" />
                            <x-text-input id="reference_number" name="reference_number" type="text" class="mt-1 block w-full" placeholder="Optional" />
                        </div>

                        <x-primary-button class="w-full justify-center py-3">
                            {{ __('Process Payment') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content: Ledger History -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tabs / Navigation -->
            <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 dark:border-gray-700 px-8 py-4 bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-widest flex items-center">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Transaction History
                    </h3>
                </div>

                <!-- Earnings History -->
                <div class="p-8">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Earnings (Commissions)</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] text-gray-400 border-b">
                                    <th class="pb-3 uppercase">Date</th>
                                    <th class="pb-3 uppercase">Source (Customer)</th>
                                    <th class="pb-3 uppercase">Invoice</th>
                                    <th class="pb-3 uppercase text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($commissions as $c)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                    <td class="py-4 text-gray-500">{{ $c->created_at->format('d M Y') }}</td>
                                    <td class="py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $c->customer->name ?? 'Deleted Customer' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $c->customer->customer_code ?? '' }}</div>
                                    </td>
                                    <td class="py-4 font-mono text-indigo-600 dark:text-indigo-400">{{ $c->invoice->invoice_number ?? '-' }}</td>
                                    <td class="py-4 text-right font-black text-emerald-600">+ Rp {{ number_format($c->commission_amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500 italic">No earnings recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $commissions->links() }}
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 p-8">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Withdrawals & Settlements</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] text-gray-400 border-b">
                                    <th class="pb-3 uppercase">Date</th>
                                    <th class="pb-3 uppercase">Method / Bank</th>
                                    <th class="pb-3 uppercase">Ref #</th>
                                    <th class="pb-3 uppercase text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($withdrawals as $w)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                    <td class="py-4 text-gray-500">{{ $w->created_at->format('d M Y') }}</td>
                                    <td class="py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $w->bankAccount->bank_name ?? '-' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $w->bankAccount->account_name ?? '' }}</div>
                                    </td>
                                    <td class="py-4 font-mono">{{ $w->reference_number ?? '-' }}</td>
                                    <td class="py-4 text-right font-black text-red-600">- Rp {{ number_format($w->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500 italic">No withdrawals recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $withdrawals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
