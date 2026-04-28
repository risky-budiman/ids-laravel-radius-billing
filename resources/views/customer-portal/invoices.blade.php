<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Riwayat Tagihan 🧾
            </h2>
            <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-gray-500 hover:text-indigo-600 flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700 shadow-2xl shadow-indigo-500/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Nomor Invoice</th>
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Periode Tagihan</th>
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Jatuh Tempo</th>
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Total</th>
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($invoices as $invoice)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <span class="font-bold text-gray-900 dark:text-white">#{{ $invoice->invoice_number }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->created_at->format('F Y') }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->due_date->format('d M Y') }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="font-black text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        @if($invoice->status === 'paid')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-widest">Lunas</span>
                                        @elseif($invoice->status === 'unpaid')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black uppercase tracking-widest">Belum Bayar</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[10px] font-black uppercase tracking-widest">{{ $invoice->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-6 text-right space-x-2">
                                        @if($invoice->status === 'unpaid')
                                            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id) }}" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-500/20">Bayar</a>
                                        @else
                                            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id) }}" target="_blank" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Lihat</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-20 text-center text-gray-400 italic">Belum ada riwayat tagihan ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="px-8 py-6 border-t border-gray-100 dark:border-gray-700">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
