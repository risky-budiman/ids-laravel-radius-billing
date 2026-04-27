<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Laporan Neraca (Balance Sheet)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Posisi Keuangan Per Tanggal: {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                </p>
            </div>
            <div class="flex gap-3">
                <form action="{{ route('accounting.reports.balance-sheet') }}" method="GET" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm">
                    <button type="submit" class="p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                
                <!-- LEFT SIDE: ASSETS -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-800/50">
                        <h3 class="font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-widest flex justify-between items-center">
                            <span>ASET (HARTA)</span>
                            <span class="text-xs font-bold text-indigo-400">AKTIVA</span>
                        </h3>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @foreach($assets as $account)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-3">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $account->name }}</span>
                                            <span class="text-[10px] font-mono text-gray-400">{{ $account->code }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right font-mono text-sm text-gray-900 dark:text-white">
                                        Rp {{ number_format($account->current_balance, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-indigo-600 text-white">
                                    <td class="py-4 px-4 text-sm font-black uppercase">TOTAL ASET</td>
                                    <td class="py-4 px-4 text-right font-mono text-lg font-black">Rp {{ number_format($totalAssets, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- RIGHT SIDE: LIABILITIES & EQUITY -->
                <div class="space-y-8">
                    <!-- LIABILITIES -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800/50">
                            <h3 class="font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest flex justify-between items-center">
                                <span>KEWAJIBAN (HUTANG)</span>
                                <span class="text-xs font-bold text-amber-400">PASIVA</span>
                            </h3>
                        </div>
                        <div class="p-6">
                            <table class="w-full">
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                    @forelse($liabilities as $account)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $account->name }}</span>
                                                <span class="text-[10px] font-mono text-gray-400">{{ $account->code }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right font-mono text-sm text-gray-900 dark:text-white">
                                            Rp {{ number_format($account->current_balance, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="py-4 text-center text-xs text-gray-400 italic">Tidak ada saldo kewajiban</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="bg-amber-100 dark:bg-amber-900/50">
                                        <td class="py-3 px-4 text-xs font-black text-amber-800 dark:text-amber-300 uppercase">TOTAL KEWAJIBAN</td>
                                        <td class="py-3 px-4 text-right font-mono text-sm font-black text-amber-800 dark:text-amber-300">Rp {{ number_format($totalLiabilities, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- EQUITY -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800/50">
                            <h3 class="font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest flex justify-between items-center">
                                <span>EKUITAS (MODAL)</span>
                                <span class="text-xs font-bold text-emerald-400">MODAL</span>
                            </h3>
                        </div>
                        <div class="p-6">
                            <table class="w-full">
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                    @foreach($equity as $account)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $account->name }}</span>
                                                <span class="text-[10px] font-mono text-gray-400">{{ $account->code }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right font-mono text-sm text-gray-900 dark:text-white">
                                            Rp {{ number_format($account->current_balance, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                    <!-- Current Earnings -->
                                    <tr class="bg-indigo-50/30 dark:bg-indigo-900/10">
                                        <td class="py-3">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 italic">Laba Berjalan (P&L Current)</span>
                                                <span class="text-[10px] font-mono text-gray-400">SYSTEM-NET-PROFIT</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                            Rp {{ number_format($currentEarnings, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-emerald-600 text-white">
                                        <td class="py-4 px-4 text-sm font-black uppercase">TOTAL PASIVA (H+M)</td>
                                        <td class="py-4 px-4 text-right font-mono text-lg font-black">Rp {{ number_format($totalLiabilities + $totalEquity, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Balance Check Alert -->
            <div class="mt-8">
                @if(abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01)
                <div class="bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-500 p-4 rounded-r-2xl">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-tighter">Neraca Seimbang (Balanced) - Aset sama dengan Kewajiban & Modal.</p>
                    </div>
                </div>
                @else
                <div class="bg-rose-50 dark:bg-rose-900/30 border-l-4 border-rose-500 p-4 rounded-r-2xl">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-rose-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p class="text-sm font-bold text-rose-800 dark:text-rose-300 uppercase tracking-tighter">Neraca Tidak Seimbang! Selisih: Rp {{ number_format(abs($totalAssets - ($totalLiabilities + $totalEquity)), 2, ',', '.') }}</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="mt-8 flex justify-center">
                <button onclick="window.print()" class="inline-flex items-center px-8 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Neraca
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
