<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Daftar Pelanggan Referal') }}
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
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest">Pelanggan</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest">Paket Layanan</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                        <th class="p-6 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Tgl Terdaftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                        <td class="p-6">
                            <div class="font-bold text-gray-900 dark:text-white text-lg">{{ $c->name }}</div>
                            <div class="text-sm text-gray-500 font-mono tracking-tighter">{{ $c->username }} ({{ $c->customer_code }})</div>
                        </td>
                        <td class="p-6">
                            <div class="font-bold text-indigo-600 dark:text-indigo-400">{{ $c->package->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">Rp {{ number_format($c->package->price ?? 0, 0, ',', '.') }} / bln</div>
                        </td>
                        <td class="p-6 text-center">
                            @if($c->is_active)
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest">AKTIF</span>
                            @else
                                <span class="px-3 py-1 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 rounded-full text-[10px] font-black uppercase tracking-widest">SUSPENDED</span>
                            @endif
                        </td>
                        <td class="p-6 text-right text-sm text-gray-500 font-medium">
                            {{ $c->created_at->format('d M Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-12 text-center text-gray-500 italic">Anda belum memiliki pelanggan referal.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-gray-100 dark:border-gray-700">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
