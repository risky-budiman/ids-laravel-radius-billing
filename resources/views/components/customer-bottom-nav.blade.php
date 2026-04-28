<div class="fixed bottom-0 left-0 z-50 w-full h-20 bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 lg:hidden pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <div class="flex h-full w-full items-center justify-around font-medium">
        
        <!-- Dashboard -->
        <a href="{{ route('customer.dashboard') }}" class="inline-flex flex-col items-center justify-center px-5 group transition-all duration-300">
            <div class="p-2 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.dashboard') ? 'bg-indigo-50 dark:bg-indigo-500/10 scale-110' : 'group-hover:bg-gray-50 dark:group-hover:bg-gray-800' }}">
                <svg class="w-6 h-6 {{ request()->routeIs('customer.dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
            <span class="text-[10px] mt-1 {{ request()->routeIs('customer.dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-gray-400 dark:text-gray-500 font-medium' }}">Beranda</span>
        </a>

        <!-- Tagihan -->
        <a href="{{ route('customer.invoices') }}" class="inline-flex flex-col items-center justify-center px-5 group transition-all duration-300">
            <div class="p-2 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.invoices') || request()->routeIs('portal.invoice') ? 'bg-indigo-50 dark:bg-indigo-500/10 scale-110' : 'group-hover:bg-gray-50 dark:group-hover:bg-gray-800' }}">
                <svg class="w-6 h-6 {{ request()->routeIs('customer.invoices') || request()->routeIs('portal.invoice') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <span class="text-[10px] mt-1 {{ request()->routeIs('customer.invoices') || request()->routeIs('portal.invoice') ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-gray-400 dark:text-gray-500 font-medium' }}">Tagihan</span>
        </a>

        <!-- Booster -->
        <a href="{{ route('customer.boosters') }}" class="inline-flex flex-col items-center justify-center px-5 group transition-all duration-300">
            <div class="p-2 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.boosters') ? 'bg-indigo-50 dark:bg-indigo-500/10 scale-110' : 'group-hover:bg-gray-50 dark:group-hover:bg-gray-800' }}">
                <svg class="w-6 h-6 {{ request()->routeIs('customer.boosters') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <span class="text-[10px] mt-1 {{ request()->routeIs('customer.boosters') ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-gray-400 dark:text-gray-500 font-medium' }}">Booster</span>
        </a>

        <!-- Bantuan -->
        <a href="{{ route('customer.tickets.index') }}" class="inline-flex flex-col items-center justify-center px-5 group transition-all duration-300">
            <div class="p-2 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.tickets.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 scale-110' : 'group-hover:bg-gray-50 dark:group-hover:bg-gray-800' }}">
                <svg class="w-6 h-6 {{ request()->routeIs('customer.tickets.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <span class="text-[10px] mt-1 {{ request()->routeIs('customer.tickets.*') ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-gray-400 dark:text-gray-500 font-medium' }}">Bantuan</span>
        </a>

    </div>
</div>

