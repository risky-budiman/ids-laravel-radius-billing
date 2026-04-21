<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Online Users Monitor') }}
        </h2>
    </x-slot>

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Live Connections Monitor</h3>
            
            <div class="flex bg-gray-100 dark:bg-gray-900 p-1 rounded-xl shadow-inner">
                <a href="{{ route('online-users.index', ['status' => 'online']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $status === 'online' ? 'bg-white dark:bg-gray-800 text-green-600 dark:text-green-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    <span class="{{ $status === 'online' ? 'inline-block w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse' : 'hidden' }}"></span>
                    Online
                </a>
                <a href="{{ route('online-users.index', ['status' => 'offline']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $status === 'offline' ? 'bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    Offline
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            @if($status === 'online')
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">MAC Address</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">NAS / Router</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Traffic (IN/OUT)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($onlineUsers as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $session->username }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 font-mono text-sm">{{ $session->framedipaddress ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-sm uppercase">{{ $session->callingstationid }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $session->nasipaddress }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ gmdate("H:i:s", $session->acctsessiontime) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300 text-sm">
                                <span class="text-green-600 dark:text-green-400">↓ {{ round($session->acctoutputoctets / 1048576, 2) }} MB</span> / 
                                <span class="text-blue-600 dark:text-blue-400">↑ {{ round($session->acctinputoctets / 1048576, 2) }} MB</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No active dial-in sessions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @else
            <!-- OFFLINE USERS TABLE -->
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Name</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone / Contact</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Indicator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($offlineUsers as $customer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $customer->username }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">{{ $customer->name }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $customer->phone ?: 'No Phone' }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $customer->package->name ?? 'Unassigned' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    Offline
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">All registered subscribers are currently Online!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
            @if($status === 'online')
                {{ $onlineUsers->appends(['status' => 'online'])->links() }}
            @else
                {{ $offlineUsers->appends(['status' => 'offline'])->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
