<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <!-- Stats Overview -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat Card 1 -->
        <div class="glass relative overflow-hidden bg-white/50 dark:bg-gray-800/50 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Subscribers</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalSubscribers) }}</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="glass relative overflow-hidden bg-white/50 dark:bg-gray-800/50 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Accounts</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($activeUsers) }}</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="glass relative overflow-hidden bg-white/50 dark:bg-gray-800/50 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Monthly Revenue</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="glass relative overflow-hidden bg-white/50 dark:bg-gray-800/50 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700/50 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Unpaid Invoices</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($unpaidInvoices) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
            <div class="space-y-4">
                <a href="{{ route('customers.create') }}" class="w-full flex items-center p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 dark:hover:border-indigo-800 transition-colors group">
                    <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Add Subscriber</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Register a new PPPoE/Hotspot user</p>
                    </div>
                </a>
                <button class="w-full flex items-center p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-green-50 dark:hover:bg-green-900/20 hover:border-green-200 dark:hover:border-green-800 transition-colors group">
                    <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400">Generate Invoices</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Create new billing cycle invoices</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="lg:col-span-2 glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Live Traffic & Online Users</h3>
            </div>
            <div class="p-6 flex flex-col items-center justify-center min-h-[300px] text-gray-500 dark:text-gray-400">
                <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                <p>Chart data will be available once RADIUS accounting is active.</p>
            </div>
        </div>
    </div>
</x-app-layout>
