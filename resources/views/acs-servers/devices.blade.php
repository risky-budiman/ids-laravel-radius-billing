<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>CPE Integrations</span>
                    <span>/</span>
                    <span class="text-indigo-500 dark:text-indigo-400">GenieACS</span>
                </div>
                <h2 class="font-black text-3xl text-gray-800 dark:text-gray-100 tracking-tight leading-none">
                    {{ __('ONT Device Management') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Monitor and manage subscriber customer premises equipment via GenieACS protocol</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="px-5 py-2.5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/20 dark:to-purple-950/20 rounded-2xl border border-indigo-100/50 dark:border-indigo-900/30 shadow-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    <span class="text-xs font-extrabold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Total CPEs: {{ $paginatedDevices->total() }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="deviceTagManager()">
        <!-- Active Tag Banner if filtered -->
        @if(!empty($tagFilter))
            <div class="p-3.5 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200 uppercase tracking-wider">Filtered by Tag:</span>
                    <span class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white rounded-xl text-xs font-black font-mono shadow-sm">
                        {{ $tagFilter }}
                    </span>
                </div>
                <a href="{{ route('acs-servers.devices', ['server_id' => request('server_id'), 'q' => request('q'), 'limit' => request('limit')]) }}" class="inline-flex items-center px-3 py-1 bg-white dark:bg-gray-800 hover:bg-rose-50 dark:hover:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold transition-all border border-rose-200 dark:border-rose-800">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Clear Tag Filter
                </a>
            </div>
        @endif

        <!-- Search & Server Select Filter Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none p-4">
            <form action="{{ route('acs-servers.devices') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
                @if(!empty($tagFilter))
                    <input type="hidden" name="tag" value="{{ $tagFilter }}">
                @endif

                <!-- Search Input with Modern Icon -->
                <div class="relative w-full md:flex-grow">
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="q" value="{{ $search }}" 
                        placeholder="Search by ID Pelanggan (Username), Tag, Serial Number, or IP..." 
                        class="w-full pl-12 pr-10 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none" autocomplete="off">
                    
                    @if($search)
                        <a href="{{ route('acs-servers.devices', ['server_id' => request('server_id'), 'tag' => request('tag'), 'limit' => request('limit')]) }}" class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-rose-500 transition-colors" title="Clear Search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
                
                <!-- Server Selector -->
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <select name="server_id" class="w-full pl-11 pr-10 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm font-bold text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer appearance-none">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" {{ ($selectedServer->id ?? null) == $server->id ? 'selected' : '' }}>
                                {{ $server->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
                
                <!-- Limit Selector -->
                <div class="relative w-full md:w-36">
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </div>
                    <select name="limit" onchange="this.form.submit()" class="w-full pl-11 pr-10 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm font-bold text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer appearance-none">
                        <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10 / Page</option>
                        <option value="25" {{ request('limit') == 25 || !request('limit') ? 'selected' : '' }}>25 / Page</option>
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 / Page</option>
                        <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100 / Page</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full md:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-2xl font-bold text-sm transition-all shadow-lg shadow-indigo-500/20 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"></path></svg>
                    Apply Filter
                </button>
            </form>
        </div>

        @if($error)
            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-400 rounded-2xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p>{{ $error }}</p>
            </div>
        @endif

        <!-- Device Grid/Table Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/30 border-b border-gray-100 dark:border-gray-700/50">
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">IDPEL</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tags</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Device ID / Serial</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Product Class</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rx Power</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Last Inform</th>
                            <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($paginatedDevices as $device)
                            @php
                                $id = $device['_id'] ?? 'Unknown';
                                
                                // Tags extraction
                                $rawTags = $device['_tags'] ?? $device['Tags'] ?? [];
                                $tags = is_array($rawTags) ? $rawTags : (is_string($rawTags) ? array_filter(explode(',', $rawTags)) : []);

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
                                
                                // Product Class Fallbacks (standard first, VP fallback)
                                $productClass = $device['_deviceId']['_ProductClass']
                                    ?? $device['DeviceID']['ProductClass']['_value']
                                    ?? $device['Device']['DeviceInfo']['ProductClass']['_value'] 
                                    ?? $device['InternetGatewayDevice']['DeviceInfo']['ProductClass']['_value'] 
                                    ?? $device['Device']['DeviceInfo']['ModelName']['_value']
                                    ?? $device['InternetGatewayDevice']['DeviceInfo']['ModelName']['_value']
                                    ?? $device['VirtualParameters']['ProductClass']['_value'] 
                                    ?? 'N/A';
                                
                                // IP Address Fallbacks (standard first, VP fallback)
                                $ip = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                                    ?? $device['VirtualParameters']['wanip']['_value']
                                    ?? $device['VirtualParameters']['IP']['_value'] 
                                    ?? 'N/A';

                                $lastInform = isset($device['_lastInform']) ? \Carbon\Carbon::parse($device['_lastInform'])->diffForHumans() : 'Never';

                                // PPPoE Username / IDPEL Fallbacks (standard first, VP fallback)
                                $idpel = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['Username']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['Username']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][2]['Username']['_value']
                                    ?? $device['Device']['PPP']['Interface'][1]['Username']['_value']
                                    ?? $device['Device']['PPP']['Interface'][2]['Username']['_value']
                                    ?? $device['VirtualParameters']['pppUsername']['_value']
                                    ?? 'N/A';

                                 // Rx Power Fallbacks & Scaling (standard vendor paths first, VP fallback)
                                  $rxPowerRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['RXPower']['_value']
                                      ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['RxPower']['_value']
                                      ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['X_ZTE-COM_WANPONInterfaceConfig']['RXPower']['_value']
                                      ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['RxPower']['_value']
                                      ?? $device['InternetGatewayDevice']['X_ALU_OntOpticalParam']['RXPower']['_value']
                                      ?? $device['Device']['Optical']['Interface'][1]['OpticalSignalLevel']['_value']
                                      ?? $device['Device']['Optical']['Interface'][1]['OpticalPowerRx']['_value']
                                      ?? $device['VirtualParameters']['getponrx']['_value']
                                      ?? null;

                                 $rxPower = null;
                                 $rxPowerColor = 'text-gray-400';
                                 if ($rxPowerRaw !== null) {
                                     $rxPower = (float) $rxPowerRaw;
                                     if ($rxPower < -100) {
                                         $rxPower = $rxPower / 100;
                                     }
                                     if ($rxPower < -1000) {
                                         $rxPower = $rxPower / 10;
                                     }
                                     
                                     if ($rxPower >= -24) {
                                         $rxPowerColor = 'text-green-600 dark:text-green-400 font-extrabold';
                                     } elseif ($rxPower >= -27) {
                                         $rxPowerColor = 'text-amber-500 dark:text-amber-400 font-extrabold';
                                     } else {
                                         $rxPowerColor = 'text-rose-600 dark:text-rose-400 font-black animate-pulse';
                                     }
                                 }
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all duration-200">
                                <td class="px-6 py-4.5">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200 font-mono">
                                        {{ explode('@', $idpel)[0] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4.5">
                                    <div class="flex items-center gap-1.5 group">
                                        @if(!empty($tags))
                                            <div class="flex flex-wrap gap-1 max-w-[170px]">
                                                @foreach($tags as $tag)
                                                    <a href="{{ route('acs-servers.devices', ['server_id' => $selectedServer->id, 'tag' => trim($tag)]) }}" class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold tracking-tight bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 dark:bg-indigo-900/40 dark:hover:bg-indigo-600 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/60 font-mono transition-all duration-150" title="Click to filter by tag '{{ trim($tag) }}'">
                                                        {{ trim($tag) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                            <button type="button" @click="openTagModal('{{ addslashes($id) }}', {{ json_encode(array_values((array)$tags)) }}, '{{ addslashes($serial) }}')" class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all" title="Edit Tags">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                        @else
                                            <button type="button" @click="openTagModal('{{ addslashes($id) }}', [], '{{ addslashes($serial) }}')" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-300 bg-gray-50 hover:bg-indigo-50 dark:bg-gray-800 dark:hover:bg-indigo-950/40 border border-dashed border-gray-200 dark:border-gray-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Tag
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4.5">
                                    <div class="flex flex-col">
                                        <a href="{{ route('acs-servers.device-details', ['deviceId' => $id, 'server_id' => $selectedServer->id]) }}" class="text-sm font-extrabold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-mono tracking-tight">
                                            {{ $serial }}
                                        </a>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4.5">
                                    <span class="px-3 py-1 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold border border-gray-100 dark:border-gray-800">
                                        {{ $productClass }}
                                    </span>
                                </td>
                                <td class="px-6 py-4.5">
                                    <span class="text-sm font-mono font-semibold text-gray-600 dark:text-gray-400">
                                        {{ $ip }}
                                    </span>
                                </td>
                                <td class="px-6 py-4.5">
                                    @if($rxPower !== null)
                                        <span class="text-sm font-mono {{ $rxPowerColor }}">
                                            {{ number_format($rxPower, 2) }} dBm
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full {{ str_contains($lastInform, 'second') || str_contains($lastInform, 'minute') ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $lastInform }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4.5">
                                    <div class="flex justify-center items-center gap-4">
                                        <a href="{{ route('acs-servers.show-device', ['deviceId' => $id, 'server_id' => $selectedServer->id]) }}" target="_blank" class="px-3 py-1.5 text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-bold bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-indigo-100 dark:hover:border-indigo-900/30 transition-all">
                                            View Raw
                                        </a>
                                        <a href="{{ route('acs-servers.device-details', ['deviceId' => $id, 'server_id' => $selectedServer->id]) }}" class="px-4 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 font-bold rounded-xl shadow-md shadow-indigo-500/10 transition-all hover:-translate-y-0.5">
                                            Manage CPE
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">No Devices Found</h4>
                                    <p class="text-xs text-gray-400">
                                        @if($selectedServer)
                                            Try adjusting your filter or search query.
                                        @else
                                            Please configure at least one active ACS server.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-6 py-5 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700/50">
                @if($paginatedDevices->hasPages())
                    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
                        <!-- Mobile View (Previous / Next) -->
                        <div class="flex justify-between flex-1 sm:hidden">
                            @if($paginatedDevices->onFirstPage())
                                <span class="inline-flex items-center px-4 py-2 text-xs font-bold text-gray-400 bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800 rounded-xl cursor-default select-none">
                                    Previous
                                </span>
                            @else
                                <a href="{{ $paginatedDevices->previousPageUrl() }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 active:scale-95 transition-all">
                                    Previous
                                </a>
                            @endif

                            @if($paginatedDevices->hasMorePages())
                                <a href="{{ $paginatedDevices->nextPageUrl() }}" class="inline-flex items-center px-4 py-2 ml-3 text-xs font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 active:scale-95 transition-all">
                                    Next
                                </a>
                            @else
                                <span class="inline-flex items-center px-4 py-2 ml-3 text-xs font-bold text-gray-400 bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800 rounded-xl cursor-default select-none">
                                    Next
                                </span>
                            @endif
                        </div>

                        <!-- Desktop View -->
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    Showing
                                    <span class="font-extrabold text-gray-800 dark:text-gray-200">{{ $paginatedDevices->firstItem() }}</span>
                                    to
                                    <span class="font-extrabold text-gray-800 dark:text-gray-200">{{ $paginatedDevices->lastItem() }}</span>
                                    of
                                    <span class="font-extrabold text-gray-800 dark:text-gray-200">{{ $paginatedDevices->total() }}</span>
                                    devices
                                </p>
                            </div>

                            <div>
                                <span class="relative z-0 inline-flex shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-850 bg-white dark:bg-gray-900 p-1 gap-1">
                                    {{-- First Page Link --}}
                                    @if($paginatedDevices->onFirstPage())
                                        <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-300 dark:text-gray-700 cursor-default rounded-xl select-none">
                                            &laquo;
                                        </span>
                                    @else
                                        <a href="{{ $paginatedDevices->url(1) }}" class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 rounded-xl transition-all" title="First Page">
                                            &laquo;
                                        </a>
                                    @endif

                                    {{-- Previous Page Link --}}
                                    @if($paginatedDevices->onFirstPage())
                                        <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-300 dark:text-gray-700 cursor-default rounded-xl select-none">
                                            &lsaquo;
                                        </span>
                                    @else
                                        <a href="{{ $paginatedDevices->previousPageUrl() }}" class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 rounded-xl transition-all" title="Previous Page">
                                            &lsaquo;
                                        </a>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $currentPage = $paginatedDevices->currentPage();
                                        $lastPage = $paginatedDevices->lastPage();
                                        $onEachSide = 1; 
                                        $showedDotsLeft = false;
                                        $showedDotsRight = false;
                                    @endphp

                                    @foreach($paginatedDevices->links()->elements as $element)
                                        @if(is_array($element))
                                            @foreach($element as $page => $url)
                                                @if($page == 1 || $page == $lastPage || abs($page - $currentPage) <= $onEachSide)
                                                    @if($page == $currentPage)
                                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-extrabold text-white bg-indigo-600 rounded-xl shadow-md shadow-indigo-500/25 select-none">{{ $page }}</span>
                                                    @else
                                                        <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 rounded-xl transition-all">{{ $page }}</a>
                                                    @endif
                                                @elseif($page < $currentPage && !$showedDotsLeft)
                                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-gray-400 dark:text-gray-500 cursor-default select-none">...</span>
                                                    @php $showedDotsLeft = true; @endphp
                                                @elseif($page > $currentPage && !$showedDotsRight)
                                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-gray-400 dark:text-gray-500 cursor-default select-none">...</span>
                                                    @php $showedDotsRight = true; @endphp
                                                @endif
                                            @endforeach
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if($paginatedDevices->hasMorePages())
                                        <a href="{{ $paginatedDevices->nextPageUrl() }}" class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 rounded-xl transition-all" title="Next Page">
                                            &rsaquo;
                                        </a>
                                    @else
                                        <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-300 dark:text-gray-700 cursor-default rounded-xl select-none">
                                            &rsaquo;
                                        </span>
                                    @endif

                                    {{-- Last Page Link --}}
                                    @if($paginatedDevices->hasMorePages())
                                        <a href="{{ $paginatedDevices->url($paginatedDevices->lastPage()) }}" class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 rounded-xl transition-all" title="Last Page">
                                            &raquo;
                                        </a>
                                    @else
                                        <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-gray-300 dark:text-gray-700 cursor-default rounded-xl select-none">
                                            &raquo;
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </nav>
                @endif
            </div>
        </div>

        <!-- Tag Edit Modal -->
        <div x-show="showTagModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-700 p-6" @click.away="showTagModal = false">
                <!-- Modal Header -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Device Tags</h3>
                        <p class="text-xs text-gray-500 font-mono" x-text="'Device: ' + (activeSerial || activeDeviceId)"></p>
                    </div>
                    <button type="button" @click="showTagModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">&times;</button>
                </div>

                <!-- Current Tags list / pills -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Assigned Tags</label>
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-2xl border border-gray-100 dark:border-gray-800 min-h-[50px] flex flex-wrap gap-1.5 items-center">
                        <template x-for="(tag, index) in activeTags" :key="tag">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-mono">
                                <span x-text="tag"></span>
                                <button type="button" @click="removeTag(index)" class="ml-1.5 text-indigo-400 hover:text-rose-500 font-bold text-sm">&times;</button>
                            </span>
                        </template>
                        <template x-if="activeTags.length === 0">
                            <span class="text-xs text-gray-400 italic">No tags assigned. Type below to add.</span>
                        </template>
                    </div>
                </div>

                <!-- Add New Tag Input -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Add New Tag</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="newTagInput" @keydown.enter.prevent="addInputTag()" placeholder="Enter tag name..." class="flex-grow px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <button type="button" @click="addInputTag()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                            Add
                        </button>
                    </div>
                </div>

                <!-- Quick Presets -->
                <div class="mb-6">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Quick Presets:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="preset in ['VIP', 'Isolir', 'GPON', 'EPON', 'Prioritas', 'Cluster-A']" :key="preset">
                            <button type="button" @click="addPresetTag(preset)" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700/60 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30 text-gray-600 dark:text-gray-300 rounded-lg text-[11px] font-semibold transition-colors">
                                + <span x-text="preset"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="showTagModal = false" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                        Cancel
                    </button>
                    <button type="button" @click="saveTags()" :disabled="isSaving" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all flex items-center disabled:opacity-50">
                        <svg x-show="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSaving ? 'Saving...' : 'Save Tags'">Save Tags</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function deviceTagManager() {
            return {
                showTagModal: false,
                activeDeviceId: '',
                activeSerial: '',
                activeTags: [],
                originalTags: [],
                newTagInput: '',
                isSaving: false,

                openTagModal(deviceId, tags, serial) {
                    this.activeDeviceId = deviceId;
                    this.activeSerial = serial || deviceId;
                    this.activeTags = Array.isArray(tags) ? [...tags] : [];
                    this.originalTags = Array.isArray(tags) ? [...tags] : [];
                    this.newTagInput = '';
                    this.showTagModal = true;
                },

                addInputTag() {
                    const tag = this.newTagInput.trim();
                    if (tag && !this.activeTags.includes(tag)) {
                        this.activeTags.push(tag);
                    }
                    this.newTagInput = '';
                },

                addPresetTag(tag) {
                    if (tag && !this.activeTags.includes(tag)) {
                        this.activeTags.push(tag);
                    }
                },

                removeTag(index) {
                    this.activeTags.splice(index, 1);
                },

                saveTags() {
                    this.isSaving = true;
                    const url = '{{ route('acs-servers.update-tags', ':id') }}'.replace(':id', encodeURIComponent(this.activeDeviceId));

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            server_id: '{{ $selectedServer->id ?? '' }}',
                            tags: this.activeTags,
                            current_tags: this.originalTags
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.showTagModal = false;
                            window.location.reload();
                        } else {
                            alert('Failed to update tags: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        alert('Network error while updating tags: ' + err.message);
                    })
                    .finally(() => {
                        this.isSaving = false;
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
