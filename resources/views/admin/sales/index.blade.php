<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Internal Sales Ledger') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Sales Staff</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $salesStaff->count() }}</h3>
                    </div>
                </div>
            </div>

            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl text-emerald-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11.401M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Outstanding Balance</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($salesStaff->sum('balance'), 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>

            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-2xl text-amber-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2v2a2 2 0 002 2h10a2 2 0 002-2v-2a2 2 0 00-2-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Incentive Paid Out</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($salesStaff->sum('total_withdrawn'), 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Staff List -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sales Staff Performance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Staff Name</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Total Earned</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Paid Out</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Current Balance</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($salesStaff as $staff)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 font-bold mr-3">
                                        {{ substr($staff->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $staff->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $staff->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($staff->total_earned, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right text-gray-500">
                                Rp {{ number_format($staff->total_withdrawn, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right">
                                <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-sm font-black">
                                    Rp {{ number_format($staff->balance, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <a href="{{ route('sales-commissions.show', $staff) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-sm">
                                    View Ledger
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500 italic">No sales staff found with role 'sales'.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Commissions -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Incentive Records</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Date</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Staff</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Customer</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Invoice</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        @foreach($recentCommissions as $commission)
                        <tr>
                            <td class="p-4 text-gray-500">{{ $commission->created_at->format('d M Y H:i') }}</td>
                            <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $commission->sales->name }}</td>
                            <td class="p-4">{{ $commission->customer->name ?? '-' }}</td>
                            <td class="p-4 text-indigo-600 dark:text-indigo-400 font-mono">{{ $commission->invoice->invoice_number ?? '-' }}</td>
                            <td class="p-4 text-right font-black text-emerald-600">+ Rp {{ number_format($commission->commission_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
