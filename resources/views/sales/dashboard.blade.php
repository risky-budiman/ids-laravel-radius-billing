<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Sales Force Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Wallet & Stats Section -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Balance Card -->
            <div class="lg:col-span-2 relative overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-800 rounded-[2rem] p-8 text-white shadow-2xl shadow-indigo-500/20">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.82v-1.91c-1.84-.41-3.37-1.58-3.37-3.64h1.91c0 1.27.95 2.1 2.5 2.1 1.63 0 2.45-.88 2.45-1.95 0-1.12-.66-1.84-2.58-2.31-2.18-.54-4.22-1.33-4.22-3.77 0-2.01 1.48-3.19 3.32-3.6V3h2.82v1.9c1.6.32 2.87 1.34 3.01 3.1h-1.91c-.13-.93-.82-1.63-2.14-1.63-1.46 0-2.32.74-2.32 1.76 0 .97.83 1.58 2.5 1.99 2.29.56 4.3 1.45 4.3 3.99 0 2.11-1.38 3.35-3.32 3.99z"/></svg>
                </div>
                <div class="relative z-10">
                    <p class="text-indigo-100 font-bold uppercase tracking-[0.2em] text-xs mb-2">Dompet Saya (Saldo Terkumpul)</p>
                    <h3 class="text-5xl font-black mb-6">Rp {{ number_format($balance, 0, ',', '.') }}</h3>
                    <div class="flex gap-4">
                        <a href="{{ route('sales.ledger') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md rounded-2xl text-sm font-black transition-all">
                            Riwayat Ledger
                        </a>
                        <div class="px-6 py-3 bg-indigo-500/30 rounded-2xl text-xs flex items-center border border-white/10 italic">
                            Sisa saldo dapat dicairkan melalui Administrator.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Earned -->
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Pendapatan</p>
                <h4 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($totalEarned, 0, ',', '.') }}</h4>
                <p class="text-[10px] text-gray-500 mt-2">Seluruh insentif yang pernah didapat.</p>
            </div>

            <!-- Total Customers -->
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Referal</p>
                <h4 class="text-2xl font-black text-gray-900 dark:text-white">{{ $referrals }} Pelanggan</h4>
                <a href="{{ route('sales.customers') }}" class="text-[10px] text-indigo-600 font-bold hover:underline mt-2 inline-block">Lihat Daftar Pelanggan &rarr;</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Commissions -->
            <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-black text-sm uppercase tracking-widest text-gray-900 dark:text-white">Insentif Terbaru</h3>
                    <a href="{{ route('sales.ledger') }}" class="text-xs text-indigo-600 font-bold hover:underline">Lihat Semua</a>
                </div>
                <div class="p-0">
                    <table class="w-full text-left border-collapse">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($recentCommissions as $commission)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $commission->customer->name ?? 'Pelanggan' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $commission->created_at->format('d M Y, H:i') }}</div>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <div class="font-black text-emerald-600">+ Rp {{ number_format($commission->commission_amount, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $commission->invoice->invoice_number ?? '' }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="px-8 py-12 text-center text-gray-500 italic text-sm">Belum ada pendapatan terekam.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Withdrawals -->
            <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-black text-sm uppercase tracking-widest text-gray-900 dark:text-white">Penarikan Saldo</h3>
                </div>
                <div class="p-0">
                    <table class="w-full text-left border-collapse">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($recentWithdrawals as $withdrawal)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="font-bold text-sm text-gray-900 dark:text-white">Pencairan Dana</div>
                                    <div class="text-[10px] text-gray-400">{{ $withdrawal->created_at->format('d M Y') }} • {{ $withdrawal->reference_number ?? 'Internal' }}</div>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <div class="font-black text-rose-600">- Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}</div>
                                    <span class="text-[9px] uppercase font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">{{ $withdrawal->status }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="px-8 py-12 text-center text-gray-500 italic text-sm">Belum ada riwayat penarikan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-6 bg-slate-50 dark:bg-slate-900/30 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] text-gray-500 leading-relaxed italic">
                        * Penarikan saldo dilakukan secara manual oleh Administrator. Harap hubungi bagian keuangan/admin untuk mengajukan penarikan sisa saldo Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
