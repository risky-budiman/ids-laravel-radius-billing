<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('bank-accounts.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-emerald-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                        Detail Mutasi: {{ $bankAccount->bank_name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $bankAccount->account_name }} • {{ $bankAccount->account_number ?: 'Tunai' }}
                    </p>
                </div>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 px-6 py-3 rounded-2xl border border-emerald-100 dark:border-emerald-800/50">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider mb-1">Saldo Saat Ini</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 tabular-nums">Rp {{ number_format($bankAccount->balance, 0, ',', '.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">Riwayat Transaksi</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/3">Deskripsi</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Referens</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Debit (Masuk)</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kredit (Keluar)</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Petugas</th>
                                @if(auth()->user()->isAdministrator())
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/50">
                            @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/25 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->transaction_date->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->transaction_date->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ $transaction->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                        {{ $transaction->reference_number ?: '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if($transaction->type === 'deposit')
                                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                            + {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if($transaction->type === 'withdrawal')
                                        <span class="text-sm font-bold text-red-600 dark:text-red-400 tabular-nums">
                                            - {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $transaction->created_by ? 'text-gray-600 dark:text-gray-400' : 'text-indigo-500' }}">
                                        {{ $transaction->creator->name ?? 'System' }}
                                    </span>
                                </td>
                                @if(auth()->user()->isAdministrator())
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <form action="{{ route('bank-transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini? Saldo akan dikoreksi otomatis.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="Hapus Transaksi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada transaksi pada rekening ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $transactions->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
