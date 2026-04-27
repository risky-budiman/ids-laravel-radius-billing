<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Laporan Laba Rugi
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                </p>
            </div>
            <div class="flex gap-3">
                <form action="{{ route('accounting.reports.profit-loss') }}" method="GET" class="flex items-center gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm">
                    <button type="submit" class="p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- P&L Content -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-700 text-center bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-widest">LAPORAN LABA RUGI</h3>
                    <p class="text-sm text-gray-500 font-medium mt-1">Sistem Penagihan Radius & Billing</p>
                </div>

                <div class="p-8">
                    <!-- INCOME SECTION -->
                    <div class="mb-10">
                        <div class="flex justify-between items-center mb-4 border-b-2 border-blue-500 pb-2">
                            <h4 class="text-lg font-black text-blue-600 uppercase tracking-tighter">I. PENDAPATAN OPERASIONAL</h4>
                            <span class="text-xs font-bold text-gray-400 uppercase">Total Pendapatan</span>
                        </div>
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @foreach($incomeAccounts as $account)
                                <tr>
                                    <td class="py-3 text-sm font-bold text-gray-700 dark:text-gray-300">{{ $account->name }}</td>
                                    <td class="py-3 text-right font-mono text-sm text-gray-900 dark:text-white">Rp {{ number_format($account->period_balance, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-blue-50/50 dark:bg-blue-900/20">
                                    <td class="py-3 px-4 text-sm font-black text-blue-700 dark:text-blue-400 uppercase">TOTAL PENDAPATAN</td>
                                    <td class="py-3 px-4 text-right font-mono text-lg font-black text-blue-700 dark:text-blue-400">Rp {{ number_format($totalIncome, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- EXPENSE SECTION -->
                    <div class="mb-10">
                        <div class="flex justify-between items-center mb-4 border-b-2 border-rose-500 pb-2">
                            <h4 class="text-lg font-black text-rose-600 uppercase tracking-tighter">II. BEBAN OPERASIONAL</h4>
                            <span class="text-xs font-bold text-gray-400 uppercase">Total Pengeluaran</span>
                        </div>
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @foreach($expenseAccounts as $account)
                                <tr>
                                    <td class="py-3 text-sm font-bold text-gray-700 dark:text-gray-300">{{ $account->name }}</td>
                                    <td class="py-3 text-right font-mono text-sm text-rose-600 dark:text-rose-400">Rp {{ number_format($account->period_balance, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-rose-50/50 dark:bg-rose-900/20">
                                    <td class="py-3 px-4 text-sm font-black text-rose-700 dark:text-rose-400 uppercase">TOTAL BEBAN</td>
                                    <td class="py-3 px-4 text-right font-mono text-lg font-black text-rose-700 dark:text-rose-400">Rp {{ number_format($totalExpense, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- SUMMARY SECTION -->
                    <div class="bg-gray-900 dark:bg-indigo-950 rounded-2xl p-8 text-white shadow-lg shadow-indigo-500/20 border-l-8 border-indigo-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-xl font-black uppercase tracking-widest">LABA (RUGI) BERSIH</h4>
                                <p class="text-xs text-indigo-300 mt-1">Setelah dikurangi seluruh beban operasional</p>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-black font-mono">
                                    Rp {{ number_format($netProfit, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-gray-50/30 dark:bg-gray-900/20 text-center">
                    <button onclick="window.print()" class="inline-flex items-center px-6 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
