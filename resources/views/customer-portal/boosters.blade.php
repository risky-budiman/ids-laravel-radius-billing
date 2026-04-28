<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Beli Kuota Tambahan (Booster) 🚀
            </h2>
            <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-gray-500 hover:text-indigo-600 flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($boosters as $booster)
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 border border-gray-100 dark:border-gray-700 shadow-2xl shadow-indigo-500/5 flex flex-col relative overflow-hidden group">
                        <!-- Decorative element -->
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                        
                        <div class="relative">
                            <div class="w-16 h-16 bg-indigo-600 text-white rounded-2xl flex items-center justify-center mb-6 shadow-xl shadow-indigo-500/30">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">{{ $booster->name }}</h3>
                            <div class="flex items-baseline gap-2 mb-6">
                                <span class="text-4xl font-black text-indigo-600">{{ $booster->quota_gb }}</span>
                                <span class="text-lg font-bold text-gray-400">GB</span>
                            </div>

                            <div class="space-y-4 mb-8">
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Kecepatan Kembali Normal
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Masa Aktif: Sesuai Paket Utama
                                </div>
                            </div>

                            <div class="mt-auto pt-6 border-t border-gray-50 dark:border-gray-700 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Harga</p>
                                    <p class="text-xl font-black text-gray-900 dark:text-white">Rp {{ number_format($booster->price, 0, ',', '.') }}</p>
                                </div>
                                <form action="{{ route('customer.boosters.buy', $booster->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest hover:scale-105 transition-transform active:scale-95 shadow-xl">Beli Sekarang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-20">
                        <p class="text-gray-400 italic">Maaf, saat ini belum ada paket Booster yang tersedia.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
