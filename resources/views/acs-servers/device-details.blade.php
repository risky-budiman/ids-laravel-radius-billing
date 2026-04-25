<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                    <a href="{{ route('acs-servers.devices') }}" class="hover:text-indigo-600">ONT Management</a>
                    <span>/</span>
                    <span class="text-gray-400">Device Details</span>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $device['_id'] ?? 'Unknown Device' }}
                </h2>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="javascript:history.back()" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-bold hover:bg-gray-50 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to List
                </a>
                <a href="{{ route('acs-servers.show-device', ['deviceId' => $deviceId, 'server_id' => $server->id]) }}" target="_blank" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
                    View Raw JSON
                </a>
                <button onclick="window.location.reload()" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25">
                    Refresh Data
                </button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 space-y-6">
        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-2xl flex items-center gap-3 text-green-700 dark:text-green-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="text-sm font-bold">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-2xl flex items-center gap-3 text-rose-700 dark:text-rose-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p class="text-sm font-bold">{{ session('error') }}</p>
            </div>
        @endif

        @if(isset($error))
            <div class="p-6 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-3xl text-center">
                <svg class="w-12 h-12 text-rose-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h3 class="text-lg font-bold text-rose-800 dark:text-rose-400 mb-2">Connection Failed</h3>
                <p class="text-sm text-rose-600 dark:text-rose-500">{{ $error }}</p>
                <div class="mt-6">
                    <a href="javascript:history.back()" class="px-6 py-2 bg-rose-600 text-white rounded-xl font-bold text-sm hover:bg-rose-700 transition-all">
                        Return to List
                    </a>
                </div>
            </div>
        @endif

        @if($device)
        @php
            // Sync extraction logic with list view
            $serial = $device['_deviceId']['_SerialNumber']
                ?? $device['DeviceID']['SerialNumber']['_value'] 
                ?? $device['Device']['DeviceInfo']['SerialNumber']['_value']
                ?? $device['InternetGatewayDevice']['DeviceInfo']['SerialNumber']['_value']
                ?? null;
            
            if (!$serial && isset($device['_id'])) {
                $serialParts = explode('-', $device['_id']);
                $serial = end($serialParts);
            }
            $serial = $serial ?? 'N/A';

            $productClass = $device['_deviceId']['_ProductClass']
                ?? $device['DeviceID']['ProductClass']['_value']
                ?? $device['VirtualParameters']['ProductClass']['_value'] 
                ?? $device['Device']['DeviceInfo']['ProductClass']['_value'] 
                ?? $device['InternetGatewayDevice']['DeviceInfo']['ProductClass']['_value'] 
                ?? $device['Device']['DeviceInfo']['ModelName']['_value']
                ?? $device['InternetGatewayDevice']['DeviceInfo']['ModelName']['_value']
                ?? 'N/A';

            $ip = $device['VirtualParameters']['wanip']['_value']
                ?? $device['VirtualParameters']['IP']['_value'] 
                ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value']
                ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value'] 
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                ?? 'N/A';

            $manufacturer = $device['_deviceId']['_Manufacturer']
                ?? $device['DeviceID']['Manufacturer']['_value'] 
                ?? $device['Device']['DeviceInfo']['Manufacturer']['_value'] 
                ?? 'N/A';
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Basic Info Card -->
            <div class="lg:col-span-1 space-y-6">
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">Device Identity</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Serial Number</p>
                            <p class="text-sm font-mono text-indigo-600 dark:text-indigo-400 font-bold">
                                {{ $serial }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Product Class / Model</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $productClass }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Manufacturer</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                                {{ $manufacturer }}
                            </p>
                        </div>
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Last Inform</p>
                            <p class="text-sm text-gray-600">
                                {{ isset($device['_lastInform']) ? \Carbon\Carbon::parse($device['_lastInform'])->diffForHumans() : 'Unknown' }}
                                <span class="text-xs block text-gray-400">{{ $device['_lastInform'] ?? '' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Connected Devices Card -->
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Connected Devices
                        </h3>
                        @php
                            $hostCount = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['HostNumberOfEntries']['_value']
                                ?? $device['Device']['Hosts']['HostNumberOfEntries']['_value']
                                ?? 0;
                        @endphp
                        <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-full text-xs font-bold">
                            {{ $hostCount }} Total
                        </span>
                    </div>

                    <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        @php $foundHosts = false; @endphp
                        @for($h = 1; $h <= 5; $h++)
                            @php
                                $host = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['Host'][$h] 
                                    ?? $device['Device']['Hosts']['Host'][$h] 
                                    ?? null;
                            @endphp
                            @if($host && (isset($host['IPAddress']['_value']) || isset($host['MACAddress']['_value'])))
                                @php $foundHosts = true; @endphp
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                                    <div class="flex justify-between items-start mb-1">
                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate max-w-[120px]">
                                            {{ $host['HostName']['_value'] ?? 'Unnamed Device' }}
                                        </p>
                                        <span class="text-[10px] px-1.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-md font-bold uppercase">Online</span>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <p class="text-[10px] font-mono text-gray-400">{{ $host['IPAddress']['_value'] ?? 'No IP' }}</p>
                                        <p class="text-[10px] font-mono text-gray-500">{{ $host['MACAddress']['_value'] ?? 'No MAC' }}</p>
                                    </div>
                                </div>
                            @endif
                        @endfor

                        @if(!$foundHosts)
                            <div class="text-center py-6">
                                <p class="text-xs text-gray-400 italic">No device details available</p>
                            </div>
                        @endif
                    </div>
                </div>


                <!-- Task Queue Status -->
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pending Tasks
                    </h3>
                    
                    <div class="space-y-3">
                        @forelse($tasks ?? [] as $task)
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl flex justify-between items-center">
                                <div>
                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-tight">{{ $task['name'] }}</p>
                                    <p class="text-[10px] text-amber-600 dark:text-amber-500">Queued: {{ \Carbon\Carbon::parse($task['timestamp'])->diffForHumans() }}</p>
                                </div>
                                <span class="px-2 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100 rounded text-[10px] font-bold uppercase">Pending</span>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-xs text-gray-400 italic">No pending tasks. Device is up to date.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                    <p class="text-sm text-gray-500 mb-4">Quick Actions</p>
                    <div class="grid grid-cols-2 gap-3">
                        <form action="{{ route('acs-servers.reboot', ['deviceId' => $deviceId]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                            <button type="submit" class="w-full p-3 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-2xl text-xs font-bold hover:bg-rose-100 transition-all border border-rose-100 dark:border-rose-800">
                                Reboot
                            </button>
                        </form>
                        <form action="{{ route('acs-servers.refresh-device', ['deviceId' => $deviceId]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                            <button type="submit" class="w-full p-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-2xl text-xs font-bold hover:bg-indigo-100 transition-all border border-indigo-100 dark:border-indigo-800">
                                Sync Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Network & Details Card -->
            <div class="lg:col-span-2 space-y-6">
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Network Status
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-2">WAN IP Address</p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-mono font-bold">
                                    {{ $ip }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-2">PPPoE Username</p>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-2 rounded-lg border border-gray-100 dark:border-gray-800">
                                {{ $device['VirtualParameters']['pppUsername']['_value'] 
                                   ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['Username']['_value'] 
                                   ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['Username']['_value']
                                   ?? 'Not Found' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Software & Hardware -->
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-6">Device Specifications</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Software Version</p>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ $device['InternetGatewayDevice']['DeviceInfo']['SoftwareVersion']['_value'] 
                                   ?? $device['DeviceID']['SoftwareVersion']['_value'] 
                                   ?? $device['Device']['DeviceInfo']['SoftwareVersion']['_value'] 
                                   ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Hardware Version</p>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ $device['InternetGatewayDevice']['DeviceInfo']['HardwareVersion']['_value'] 
                                   ?? $device['Device']['DeviceInfo']['HardwareVersion']['_value'] 
                                   ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Uptime</p>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                @php
                                    $uptimeSeconds = $device['InternetGatewayDevice']['DeviceInfo']['UpTime']['_value']
                                        ?? $device['InternetGatewayDevice']['DeviceInfo']['SoftwareVersionUpTime']['_value']
                                        ?? $device['Device']['DeviceInfo']['UpTime']['_value']
                                        ?? null;
                                    
                                    if ($uptimeSeconds) {
                                        $days = floor($uptimeSeconds / 86400);
                                        $hours = floor(($uptimeSeconds % 86400) / 3600);
                                        $minutes = floor(($uptimeSeconds % 3600) / 60);
                                        
                                        if ($days > 0) {
                                            echo "{$days}d {$hours}h";
                                        } elseif ($hours > 0) {
                                            echo "{$hours}h {$minutes}m";
                                        } else {
                                            echo "{$minutes}m";
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                @endphp
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Configuration Management -->
                <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Configuration Management
                    </h3>

                    <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $server->id }}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">PPPoE Username</label>
                                <input type="text" name="ppp_username" value="{{ $device['VirtualParameters']['pppUsername']['_value'] ?? '' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">PPPoE Password</label>
                                <input type="password" name="ppp_password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">VLAN ID</label>
                            @php
                                $currentVlan = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalPBITest']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? null;
                            @endphp
                            <div class="mb-2">
                                <span class="text-[10px] text-gray-500 mr-2">Current Value:</span>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $currentVlan ?? 'Not Found' }}</span>
                            </div>
                            <input type="number" name="vlan_id" value="{{ $currentVlan }}" placeholder="e.g. 100" class="w-full md:w-1/2 px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full md:w-auto px-8 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Push Network Configuration
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Wireless Management -->
                <div class="space-y-6">
                    @for($i = 1; $i <= 2; $i++)
                        @php
                            // Check if this instance exists in either TR-069 or TR-181
                            $igdWlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$i] ?? null;
                            $devWlanSsid = $device['Device']['WiFi']['SSID'][$i] ?? null;
                            $devWlanRadio = $device['Device']['WiFi']['Radio'][$i] ?? null;
                            $devWlanAp = $device['Device']['WiFi']['AccessPoint'][$i] ?? null;

                            // Skip if no data for this instance
                            if (!$igdWlan && !$devWlanSsid) continue;

                            $ssid = $igdWlan['SSID']['_value'] ?? $devWlanSsid['SSID']['_value'] ?? 'Unknown';
                            $band = $devWlanRadio['OperatingFrequencyBand']['_value'] ?? ($i == 2 ? '5 GHz' : '2.4 GHz');
                            $wlanEnabled = $igdWlan['Enable']['_value'] ?? $devWlanRadio['Enable']['_value'] ?? null;
                        @endphp

                        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a9.9 9.9 0 0114.142 0M2.006 8.85a15.461 15.461 0 0121.988 0"></path></svg>
                                    Wireless Management {{ $i }}
                                </h3>
                                @if($wlanEnabled === '1' || $wlanEnabled === true || $wlanEnabled === 'true')
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-bold flex items-center">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-full text-xs font-bold">
                                        Inactive
                                    </span>
                                @endif
                            </div>

                            <!-- WLAN Status Mini Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Current SSID</p>
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $ssid }}</p>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Channel</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                        {{ $igdWlan['Channel']['_value'] ?? $devWlanRadio['Channel']['_value'] ?? 'Auto' }}
                                    </p>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Security</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                        {{ $igdWlan['BeaconType']['_value'] ?? $devWlanAp['Security']['ModeEnabled']['_value'] ?? 'WPA2-PSK' }}
                                    </p>
                                </div>
                            </div>

                            <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" class="space-y-6">
                                @csrf
                                <input type="hidden" name="server_id" value="{{ $server->id }}">
                                <input type="hidden" name="instance" value="{{ $i }}">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">New SSID Name</label>
                                        <input type="text" name="wifi_ssid" value="{{ $ssid }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">New Wi-Fi Password</label>
                                        <input type="password" name="wifi_password" placeholder="Leave empty to keep current" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="w-full md:w-auto px-8 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Update SSID {{ $i }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
