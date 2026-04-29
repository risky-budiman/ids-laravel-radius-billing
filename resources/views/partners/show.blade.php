<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-xl font-bold mr-4 shadow-lg shadow-indigo-200">
                    {{ substr($partner->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                        {{ $partner->name }}
                    </h2>
                    <p class="text-sm text-gray-500">Partner Performance Dashboard</p>
                </div>
            </div>
            @if(auth()->user()->isMitra())
                <button x-data="" @click="$dispatch('open-modal', 'request-withdrawal')" class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-emerald-200 dark:shadow-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Request Withdrawal
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Earned</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-2">Rp {{ number_format($stats['total_earned'], 0, ',', '.') }}</p>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm border-l-4 border-l-indigo-500">
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Current Balance</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-2">Rp {{ number_format($stats['balance'], 0, ',', '.') }}</p>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Payouts</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-2">Rp {{ number_format($stats['total_withdrawn'], 0, ',', '.') }}</p>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Subscribers</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-2">{{ $stats['customer_count'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Commission History -->
            <div class="lg:col-span-2 space-y-6">
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white">Recent Commissions</h3>
                        <span class="text-xs text-gray-400">Last 50 transactions</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Subscriber</th>
                                    <th class="px-6 py-3">Invoice</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($commissions as $comm)
                                <tr class="text-sm">
                                    <td class="px-6 py-4 text-gray-500">{{ $comm->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $comm->customer->name }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ $comm->invoice->invoice_number }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-emerald-600">+Rp {{ number_format($comm->amount, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if($comm->status === 'earned')
                                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded-md text-[10px] font-bold uppercase">Ready</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-md text-[10px] font-bold uppercase">Withdrawn</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No commissions earned yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Subscriber List -->
            <div class="space-y-6">
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white">Your Subscribers</h3>
                    </div>
                    <div class="p-2">
                        <div class="space-y-1">
                            @forelse($customers as $customer)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-900/30 rounded-2xl transition-colors group">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 text-xs font-bold mr-3">
                                        {{ substr($customer->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $customer->name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ $customer->package->name ?? 'No Package' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] {{ $customer->is_active ? 'text-emerald-500' : 'text-rose-500' }} font-bold uppercase tracking-tighter">
                                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            @empty
                            <div class="p-6 text-center text-gray-400 italic text-sm">No subscribers linked yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="glass bg-slate-900 rounded-3xl p-6 text-white border border-slate-800">
                    <h4 class="font-bold mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Payout Information
                    </h4>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Bank</span>
                            <span class="font-bold">{{ $partner->bank_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Account #</span>
                            <span class="font-bold">{{ $partner->bank_account_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Holder</span>
                            <span class="font-bold text-xs">{{ $partner->bank_account_name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Modal -->
    <x-modal name="request-withdrawal" focusable>
        <form method="post" action="{{ route('partners.withdrawals.request') }}" class="p-8">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                Request Payout
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Your current balance is <strong>Rp {{ number_format($stats['balance'], 0, ',', '.') }}</strong>.
            </p>

            <div class="mt-6">
                <x-input-label for="amount" value="{{ __('Withdrawal Amount') }}" />
                <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" placeholder="Min. 10.000" min="10000" max="{{ $stats['balance'] }}" required />
                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3 bg-emerald-600 hover:bg-emerald-700">
                    {{ __('Submit Request') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
