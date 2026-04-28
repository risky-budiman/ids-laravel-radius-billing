<x-app-layout>
    <div class="space-y-4 animate-fade-in pb-10">
        <div class="flex items-center justify-between px-2">
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Riwayat Tagihan</h2>
        </div>

        @forelse($invoices as $invoice)
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm group active:scale-[0.98] transition-all">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">#{{ $invoice->invoice_number }}</p>
                            <h4 class="font-black text-slate-950 dark:text-white text-sm">Tagihan {{ $invoice->created_at->format('M Y') }}</h4>
                        </div>
                    </div>
                    @if($invoice->status === 'paid')
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-[8px] font-black uppercase tracking-widest rounded">Lunas</span>
                    @elseif($invoice->status === 'unpaid')
                        <span class="px-2.5 py-0.5 bg-rose-100 text-rose-700 text-[8px] font-black uppercase tracking-widest rounded">Pending</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 py-3 border-y border-gray-50 dark:border-gray-700/50 mb-4">
                    <div>
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Jatuh Tempo</p>
                        <p class="text-xs font-black text-slate-900 dark:text-gray-200">{{ $invoice->due_date->format('d M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Total Bayar</p>
                        <p class="text-xs font-black text-indigo-600 dark:text-indigo-400">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    @if($invoice->status === 'unpaid')
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id) }}" class="flex-1 bg-indigo-600 text-white text-center py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-md active:scale-95 transition-all">Bayar Sekarang</a>
                    @else
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id) }}" target="_blank" class="flex-1 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-center py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all">Lihat Detail</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl text-center border border-dashed border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 font-bold">Belum ada riwayat tagihan.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
