<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('accounting.journals.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-indigo-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Detail Jurnal: {{ $journal->reference }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Header Info -->
                <div class="p-8 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($journal->date)->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Referensi</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $journal->reference }}
                            </span>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Deskripsi</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $journal->description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="p-0">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Akun Perkiraan</th>
                                <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Debit</th>
                                <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($journal->items as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white {{ $item->credit > 0 ? 'ml-8' : '' }}">
                                            {{ $item->account->name }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-mono {{ $item->credit > 0 ? 'ml-8' : '' }}">
                                            {{ $item->account->code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 text-right font-mono text-sm {{ $item->debit > 0 ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-300' }}">
                                    {{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 2, ',', '.') : '-' }}
                                </td>
                                <td class="px-8 py-4 text-right font-mono text-sm {{ $item->credit > 0 ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-300' }}">
                                    {{ $item->credit > 0 ? 'Rp ' . number_format($item->credit, 2, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-t-2 border-gray-100 dark:border-gray-700">
                                <td class="px-8 py-6 text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">Total</td>
                                <td class="px-8 py-6 text-right font-mono text-lg font-black text-indigo-600 dark:text-indigo-400">
                                    Rp {{ number_format($journal->items->sum('debit'), 2, ',', '.') }}
                                </td>
                                <td class="px-8 py-6 text-right font-mono text-lg font-black text-indigo-600 dark:text-indigo-400">
                                    Rp {{ number_format($journal->items->sum('credit'), 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer / Metadata -->
                <div class="p-8 bg-gray-50/30 dark:bg-gray-900/20 flex justify-between items-center text-xs text-gray-400">
                    <div>
                        Terakhir diperbarui: {{ $journal->updated_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Operator: {{ $journal->creator->name ?? 'System' }}
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex justify-between gap-4">
                <button onclick="window.print()" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Bukti Jurnal
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
