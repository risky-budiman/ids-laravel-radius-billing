<x-app-layout>
    <div class="space-y-4 animate-fade-in pb-10">
        <div class="flex items-center justify-between px-2">
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Booster</h2>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($boosters as $booster)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="flex justify-between items-center mb-4">
                            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ $booster->quota_gb }}</span>
                                <span class="text-xs font-black text-gray-400">GB</span>
                            </div>
                        </div>

                        <h3 class="text-base font-black text-slate-950 dark:text-white mb-2">{{ $booster->name }}</h3>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50 dark:border-gray-700/50">
                            <div>
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Harga</p>
                                <p class="text-base font-black text-slate-950 dark:text-white">Rp {{ number_format($booster->price, 0, ',', '.') }}</p>
                            </div>
                            <form action="{{ route('customer.boosters.buy', $booster->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all shadow-md">Beli</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl text-center border border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 font-bold">Belum ada paket Booster tersedia.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
