<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Riwayat Pendapatan (Ledger)') }}
            </h2>
            <a href="{{ route('sales.dashboard') }}" class="text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest">Tanggal & Waktu</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest">Pelanggan / Sumber</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest">Nomor Invoice</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Nominal Insentif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($commissions as $c)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <td class="p-6 text-sm text-gray-500">
                            {{ $c->created_at->format('d M Y') }}
                            <div class="text-[10px] text-gray-400">{{ $c->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="p-6">
                            <div class="font-bold text-gray-900 dark:text-white">{{ $c->customer->name ?? 'Deleted' }}</div>
                            <div class="text-[10px] text-gray-500 uppercase font-mono">{{ $c->customer->customer_code ?? '' }}</div>
                        </td>
                        <td class="p-6 font-mono text-sm text-indigo-600 dark:text-indigo-400 font-bold">
                            {{ $c->invoice->invoice_number ?? '-' }}
                        </td>
                        <td class="p-6 text-right">
                            <span class="text-lg font-black text-emerald-600">+ Rp {{ number_format($c->commission_amount, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-12 text-center text-gray-500 italic">Belum ada catatan pendapatan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-gray-100 dark:border-gray-700">
            {{ $commissions->links() }}
        </div>
    </div>
</x-app-layout>
