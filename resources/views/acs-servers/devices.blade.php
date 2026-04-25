<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('ONT Device Management') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage and monitor all CPE devices from GenieACS</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800">
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Total Devices: {{ $paginatedDevices->total() }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Absolute Right Aligned Search Bar -->
        <div class="bg-white dark:bg-gray-800 h-14 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-stretch overflow-hidden">
            <form action="{{ route('acs-servers.devices') }}" method="GET" class="flex flex-grow items-center">
                <!-- Max Width Search Input -->
                <div class="flex-grow h-full">
                    <input type="text" name="q" value="{{ $search }}" 
                        placeholder="Cari Serial Number atau Device ID..." 
                        class="w-full h-full px-6 bg-transparent border-none focus:ring-0 text-sm dark:text-gray-200 placeholder-gray-400" autocomplete="off">
                </div>
                
                <div class="flex items-center h-full shrink-0">
                    @if($search)
                        <a href="{{ route('acs-servers.devices', ['server_id' => request('server_id')]) }}" class="text-gray-400 hover:text-rose-500 transition-colors px-3" title="Clear Search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif

                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>

                    <div class="relative h-full shrink-0">
                        <select name="server_id" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-600 dark:text-gray-400 min-w-[160px] cursor-pointer h-full pl-4 pr-10 appearance-none !appearance-none">
                            @foreach($servers as $server)
                                <option value="{{ $server->id }}" {{ ($selectedServer->id ?? null) == $server->id ? 'selected' : '' }}>
                                    {{ $server->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 h-full font-bold text-sm transition-all active:scale-95 flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Search
                    </button>
                </div>
            </form>
        </div>


        @if($error)
            <div class="px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-xl">
                {{ $error }}
            </div>
        @endif

        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Device ID / Serial</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Class</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Inform</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($paginatedDevices as $device)
                            @php
                                $id = $device['_id'] ?? 'Unknown';
                                
                                // Serial Number Fallbacks
                                $serial = $device['_deviceId']['_SerialNumber']
                                    ?? $device['DeviceID']['SerialNumber']['_value'] 
                                    ?? $device['Device']['DeviceInfo']['SerialNumber']['_value']
                                    ?? $device['InternetGatewayDevice']['DeviceInfo']['SerialNumber']['_value']
                                    ?? null;
                                if (!$serial) {
                                    $parts = explode('-', $id);
                                    $serial = end($parts);
                                }
                                
                                // Product Class Fallbacks
                                $productClass = $device['_deviceId']['_ProductClass']
                                    ?? $device['DeviceID']['ProductClass']['_value']
                                    ?? $device['VirtualParameters']['ProductClass']['_value'] 
                                    ?? $device['Device']['DeviceInfo']['ProductClass']['_value'] 
                                    ?? $device['InternetGatewayDevice']['DeviceInfo']['ProductClass']['_value'] 
                                    ?? $device['Device']['DeviceInfo']['ModelName']['_value']
                                    ?? $device['InternetGatewayDevice']['DeviceInfo']['ModelName']['_value']
                                    ?? 'N/A';
                                
                                // IP Address Fallbacks
                                $ip = $device['VirtualParameters']['wanip']['_value']
                                    ?? $device['VirtualParameters']['IP']['_value'] 
                                    ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value'] 
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? 'N/A';

                                $lastInform = isset($device['_lastInform']) ? \Carbon\Carbon::parse($device['_lastInform'])->diffForHumans() : 'Never';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-sm">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $serial }}</span>
                                    <p class="text-xs text-gray-400">{{ $id }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $productClass }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $ip }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $lastInform }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-3">
                                        <a href="{{ route('acs-servers.show-device', ['deviceId' => $id, 'server_id' => $selectedServer->id]) }}" target="_blank" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs font-medium">
                                            View Raw
                                        </a>
                                        <a href="{{ route('acs-servers.device-details', ['deviceId' => $id, 'server_id' => $selectedServer->id]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                            Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    @if($selectedServer)
                                        No devices found.
                                    @else
                                        Please add and select an active ACS server first.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                {{ $paginatedDevices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
