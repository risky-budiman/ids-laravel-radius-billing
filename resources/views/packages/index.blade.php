<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-3xl text-gray-900 dark:text-white tracking-tight">
                    {{ __('Service Packages') }}
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage billing plans and bandwidth profiles</p>
            </div>
            <a href="{{ route('packages.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/30 hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Package
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 animate-fade-in-down">
            <div class="px-4 py-3 bg-green-500/10 border border-green-500/20 text-green-600 dark:text-green-400 rounded-2xl flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 animate-fade-in-down">
            <div class="px-4 py-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 rounded-2xl flex items-start sm:items-center shadow-sm gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span class="font-medium text-sm leading-relaxed">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800/50 backdrop-blur-xl rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-widest">Plan Details</th>
                        <th class="px-6 py-5 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Speed Profile</th>
                        <th class="px-6 py-5 text-xs font-bold text-gray-400 uppercase tracking-widest">MikroTik Group</th>
                        <th class="px-6 py-5 text-xs font-bold text-gray-400 uppercase tracking-widest">Monthly Rate</th>
                        <th class="px-8 py-5 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($packages as $package)
                        <tr class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg mr-4 group-hover:rotate-6 transition-transform">
                                        {{ substr($package->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white text-base">{{ $package->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1 max-w-xs">{{ $package->description ?: 'No additional notes' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex flex-col items-center">
                                    <div class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700/50 rounded-full font-mono text-sm">
                                        <span class="text-blue-600 dark:text-blue-400">↑{{ $package->upload_speed ?: '∞' }}</span>
                                        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                                        <span class="text-indigo-600 dark:text-indigo-400">↓{{ $package->download_speed ?: '∞' }}</span>
                                    </div>
                                    
                                    <!-- Detailed Limitations -->
                                    <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-[10px] uppercase font-bold tracking-tighter">
                                        @if($package->burst_limit_up || $package->burst_limit_down)
                                            <div class="text-amber-600 flex items-center col-span-2 justify-center mb-1 bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                Burst: {{ $package->burst_limit_up ?: '-' }} / {{ $package->burst_limit_down ?: '-' }}
                                            </div>
                                        @endif
                                        
                                        @if($package->limit_at_up || $package->limit_at_down)
                                            <div class="text-gray-500 dark:text-gray-400">
                                                Min: <span class="text-gray-700 dark:text-gray-200">{{ $package->limit_at_up ?: '-' }} / {{ $package->limit_at_down ?: '-' }}</span>
                                            </div>
                                        @endif

                                        <div class="text-gray-500 dark:text-gray-400 text-right">
                                            Prio: <span class="text-indigo-600 dark:text-indigo-400">{{ $package->priority ?: 8 }}</span>
                                        </div>
                                    </div>

                                    @if($package->enable_fup)
                                        <div class="mt-2 flex justify-center">
                                            <span class="text-[10px] px-2 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-full font-bold tracking-tight">FUP: {{ $package->fup_limit_gb }}GB</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                @if($package->mikrotik_group)
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $package->mikrotik_group }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-6">
                                <div class="text-lg font-extrabold text-gray-900 dark:text-white">
                                    <span class="text-xs font-medium text-gray-500 mr-0.5">Rp</span>{{ number_format($package->price, 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('packages.edit', $package) }}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Edit Package">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Delete this package? Ensure no active subscribers are using it.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Delete Package">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 dark:text-white font-bold text-lg">No Packages Found</h3>
                                    <p class="text-gray-500 max-w-xs mt-1">Get started by creating your first internet service package.</p>
                                    <a href="{{ route('packages.create') }}" class="mt-6 text-indigo-600 font-bold hover:underline">+ Add New Package</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
            <div class="px-8 py-6 border-t border-gray-50 dark:border-gray-700/50">
                {{ $packages->links() }}
            </div>
        @endif
    </div>

    <style>
        .animate-fade-in-down {
            animation: fadeInDown 0.5s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
