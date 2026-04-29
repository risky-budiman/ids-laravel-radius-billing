<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    General Ledger (Jurnal Umum)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Daftar semua transaksi akuntansi manual dan otomatis.
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('accounting.journals.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-indigo-500/20">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Jurnal Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Referensi</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Jurnal</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Debit</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Kredit</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @forelse($journals as $journal)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors align-top">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('accounting.journals.show', $journal) }}" class="group">
                                        <span class="font-mono text-xs font-bold bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-900 dark:text-white group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-indigo-600 transition-colors">
                                            {{ $journal->reference }}
                                        </span>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $journal->description }}
                                    <div class="text-[10px] text-gray-400 mt-1 italic">
                                        Input by: {{ $journal->creator->name ?? 'System' }} 
                                        ({{ $journal->created_at->format('H:i') }})
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @foreach($journal->items as $item)
                                        <div class="flex justify-between text-xs">
                                            <span class="{{ $item->credit > 0 ? 'ml-4 text-gray-500' : 'font-bold text-gray-700 dark:text-gray-300' }}">
                                                {{ $item->account->code }} - {{ $item->account->name }}
                                            </span>
                                            <span class="font-mono text-gray-400">
                                                {{ $item->debit > 0 ? 'Dr' : 'Cr' }}
                                            </span>
                                        </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-sm font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($journal->items->sum('debit'), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-sm font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($journal->items->sum('credit'), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center space-x-2">
                                        <a href="{{ route('accounting.journals.show', $journal) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Lihat Detail">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                        @if(auth()->user()->isAdmin())
                                        <form action="{{ route('accounting.journals.destroy', $journal) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurnal ini? Tindakan ini juga akan menghapus transaksi bank terkait.')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Jurnal">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada transaksi jurnal tercatat.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($journals->hasPages())
                <div class="px-6 py-4 border-t border-gray-50 dark:border-gray-700">
                    {{ $journals->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
