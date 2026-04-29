<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Partner & Reseller Management') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage local agents and track their performance.</p>
            </div>
            <a href="{{ route('partners.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-indigo-200 dark:shadow-none transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Add New Partner
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Partners</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $partners->total() }}</p>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-indigo-600">
                <p class="text-sm font-medium text-gray-500">Active Commissions</p>
                <p class="text-2xl font-black mt-1">Rp {{ number_format(\App\Models\PartnerCommission::where('status', 'earned')->sum('amount'), 0, ',', '.') }}</p>
            </div>
            <div class="md:col-span-2">
                <a href="{{ route('partners.withdrawals') }}" class="glass bg-indigo-600 hover:bg-indigo-700 p-6 rounded-3xl border border-indigo-500 shadow-lg shadow-indigo-200 dark:shadow-none flex items-center justify-between group transition-all">
                    <div>
                        <p class="text-sm font-medium text-white/80">Pending Withdrawals</p>
                        <p class="text-2xl font-black text-white mt-1">{{ \App\Models\PartnerWithdrawal::where('status', 'pending')->count() }} Requests</p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-2xl text-white group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </a>
            </div>
        </div>

        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Partner Name</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Subscribers</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Commission Rate</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($partners as $partner)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold mr-3">
                                        {{ substr($partner->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $partner->name }}</div>
                                        <div class="text-xs text-gray-500">Joined {{ $partner->created_at->format('M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $partner->email }}</div>
                                <div class="text-xs text-gray-500">{{ $partner->bank_name ?? 'No bank info' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-full text-sm font-bold text-gray-700 dark:text-gray-300">
                                    {{ $partner->customers_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($partner->commission_rate, 0) }}{{ $partner->commission_type === 'percentage' ? '%' : '' }}
                                </div>
                                <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $partner->commission_type }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('partners.show', $partner) }}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="View Dashboard">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                                    </a>
                                    <a href="{{ route('partners.edit', $partner) }}" class="p-2 text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 00-2 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($partners->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                {{ $partners->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
