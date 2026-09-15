<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 font-semibold">
                    <a href="{{ route('acs-servers.devices') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">ONT Management</a>
                    <span>/</span>
                    <span class="text-indigo-500 dark:text-indigo-400">Device Details</span>
                </div>
                <h2 class="font-black text-3xl text-gray-800 dark:text-gray-100 tracking-tight leading-none">
                    {{ $device['_id'] ?? 'Unknown Device' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Configure and diagnose subscriber customer premises equipment via TR-069 protocol</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <a href="javascript:history.back()" class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-2xl text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-700/50 shadow-sm transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to List
                </a>
                <a href="{{ route('acs-servers.show-device', ['deviceId' => $deviceId, 'server_id' => $server->id]) }}" target="_blank" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-gray-600 dark:text-gray-400 rounded-2xl text-xs font-bold hover:bg-indigo-50 dark:hover:bg-indigo-950/30 shadow-sm transition-all">
                    View Raw JSON
                </a>
                <button onclick="window.location.reload()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-2xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5"></path></svg>
                    Refresh View
                </button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-400 rounded-2xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if(isset($error))
            <div class="p-8 bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-md text-center max-w-lg mx-auto">
                <svg class="w-12 h-12 text-rose-500 mx-auto mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2">Connection Failed</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ $error }}</p>
                <a href="javascript:history.back()" class="inline-flex px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10">
                    Return to Devices List
                </a>
            </div>
        @endif

        @if($device)
        @php
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
                ?? $device['Device']['DeviceInfo']['ProductClass']['_value'] 
                ?? $device['InternetGatewayDevice']['DeviceInfo']['ProductClass']['_value'] 
                ?? $device['Device']['DeviceInfo']['ModelName']['_value']
                ?? $device['InternetGatewayDevice']['DeviceInfo']['ModelName']['_value']
                ?? $device['VirtualParameters']['ProductClass']['_value'] 
                ?? 'N/A';
 
            $ip = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value']
                ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value']
                ?? $device['VirtualParameters']['wanip']['_value']
                ?? $device['VirtualParameters']['IP']['_value'] 
                ?? 'N/A';
 
            $manufacturer = $device['_deviceId']['_Manufacturer']
                ?? $device['DeviceID']['Manufacturer']['_value'] 
                ?? $device['Device']['DeviceInfo']['Manufacturer']['_value'] 
                ?? 'N/A';

            // Rx Power (standard vendor paths first, VP fallback)
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
            if ($rxPowerRaw !== null) {
                $rxPower = (float) $rxPowerRaw;
                if ($rxPower < -100) $rxPower = $rxPower / 100;
                if ($rxPower < -1000) $rxPower = $rxPower / 10;
            }

            // Tx Power
            $txPowerRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['TXPower']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['TxPower']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['TxPower']['_value']
                ?? $device['Device']['Optical']['Interface'][1]['OpticalPowerTx']['_value']
                ?? null;
            $txPower = null;
            if ($txPowerRaw !== null) {
                $txPower = (float) $txPowerRaw;
                if ($txPower > 100) $txPower = $txPower / 100;
                if ($txPower > 1000) $txPower = $txPower / 10;
            }

            // Temperature
            $tempRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['TransceiverTemperature']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['Temperature']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['Temp']['_value']
                ?? null;
            $temperature = null;
            if ($tempRaw !== null) {
                $temperature = (float) $tempRaw;
                if ($temperature > 200) $temperature = $temperature / 100;
                if ($temperature > 1000) $temperature = $temperature / 10;
            }

            // Bias Current
            $biasRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['BiasCurrent']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['BiasCurrent']['_value']
                ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['Bias']['_value']
                ?? null;
            $bias = null;
            if ($biasRaw !== null) {
                $bias = (float) $biasRaw;
                if ($bias > 1000) $bias = $bias / 1000;
            }
        @endphp

        <!-- Unified Tab Layout Container (Full Width) -->
        <div class="space-y-6">
            <!-- Modern Tabs Selector -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none flex flex-wrap gap-2">
                <button onclick="switchTab('tab-overview')" id="btn-tab-overview" class="tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-500/10 transition-all">
                    System Overview
                </button>
                <button onclick="switchTab('tab-diagnostics')" id="btn-tab-diagnostics" class="tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all">
                    Diagnostics & Clients
                </button>
                <button onclick="switchTab('tab-wan')" id="btn-tab-wan" class="tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all">
                    WAN Settings
                </button>
                <button onclick="switchTab('tab-wireless')" id="btn-tab-wireless" class="tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all">
                    Wi-Fi Configuration
                </button>
            </div>

            <!-- Tab 1: System Overview -->
            <div id="tab-overview" class="tab-content space-y-6">
                <!-- CPE Identity Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                        Device Identity
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Serial Number</p>
                            <p class="text-sm font-mono text-indigo-600 dark:text-indigo-400 font-extrabold">{{ $serial }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Product Class / Model</p>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $productClass }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Manufacturer</p>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $manufacturer }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Last Inform</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold truncate">
                                {{ isset($device['_lastInform']) ? \Carbon\Carbon::parse($device['_lastInform'])->diffForHumans() : 'Unknown' }}
                                <span class="text-[9px] block text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $device['_lastInform'] ?? '' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Hardware/Software Spec Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                        Hardware Specifications
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-1">Software version</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                {{ $device['InternetGatewayDevice']['DeviceInfo']['SoftwareVersion']['_value'] 
                                   ?? $device['DeviceID']['SoftwareVersion']['_value'] 
                                   ?? $device['Device']['DeviceInfo']['SoftwareVersion']['_value'] 
                                   ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-1">Hardware version</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                {{ $device['InternetGatewayDevice']['DeviceInfo']['HardwareVersion']['_value'] 
                                   ?? $device['Device']['DeviceInfo']['HardwareVersion']['_value'] 
                                   ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-1">Device Uptime</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
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
            </div>

            <!-- Tab 2: Diagnostics & Clients -->
            <div id="tab-diagnostics" class="tab-content space-y-6 hidden">
                <!-- Optical Transceiver Diagnostics Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                        Optical Transceiver Diagnostics (GPON/EPON)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Rx Optical Power</p>
                            @if($rxPower !== null)
                                @php
                                    $colorClass = 'text-green-600 dark:text-green-400';
                                    if ($rxPower < -27) {
                                        $colorClass = 'text-rose-600 dark:text-rose-400 font-black animate-pulse';
                                    } elseif ($rxPower < -24) {
                                        $colorClass = 'text-amber-500 dark:text-amber-400';
                                    }
                                @endphp
                                <p class="text-base font-mono font-extrabold {{ $colorClass }}">
                                    {{ number_format($rxPower, 2) }} dBm
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Tx Optical Power</p>
                            @if($txPower !== null)
                                <p class="text-base font-mono font-extrabold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($txPower, 2) }} dBm
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Transceiver Temperature</p>
                            @if($temperature !== null)
                                <p class="text-base font-mono font-extrabold text-gray-750 dark:text-gray-300">
                                    {{ number_format($temperature, 1) }} °C
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Laser Bias Current</p>
                            @if($bias !== null)
                                <p class="text-base font-mono font-extrabold text-gray-750 dark:text-gray-300">
                                    {{ number_format($bias, 2) }} mA
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Active WLAN Hosts Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                        @php
                            $hostCount = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['HostNumberOfEntries']['_value']
                                ?? $device['Device']['Hosts']['HostNumberOfEntries']['_value']
                                ?? 0;
                        @endphp
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                                Active WLAN Hosts
                            </h3>
                            <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-[10px] font-extrabold uppercase">
                                {{ $hostCount }} Connected
                            </span>
                        </div>

                        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1.5 custom-scrollbar">
                            @php $foundHosts = false; @endphp
                            @for($h = 1; $h <= 10; $h++)
                                @php
                                    $host = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['Host'][$h] 
                                        ?? $device['Device']['Hosts']['Host'][$h] 
                                        ?? null;
                                @endphp
                                @if($host && (isset($host['IPAddress']['_value']) || isset($host['MACAddress']['_value'])))
                                    @php $foundHosts = true; @endphp
                                    <div class="p-3 bg-gray-50/50 dark:bg-gray-900/50 rounded-2xl border border-gray-100/50 dark:border-gray-800 flex justify-between items-center">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                                {{ $host['HostName']['_value'] ?? 'Unnamed Client' }}
                                            </p>
                                            <p class="text-[9px] font-mono text-gray-400 mt-0.5">{{ $host['IPAddress']['_value'] ?? 'No IP' }} • {{ $host['MACAddress']['_value'] ?? 'No MAC' }}</p>
                                        </div>
                                        <span class="text-[9px] px-2 py-0.5 bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 rounded-md font-bold uppercase border border-green-100/30">Active</span>
                                    </div>
                                @endif
                            @endfor

                            @if(!$foundHosts)
                                <div class="text-center py-8">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <p class="text-xs text-gray-400 italic">No connected clients details found.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- ACS Task Queue -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                        <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                            Pending Queue Tasks
                        </h3>
                        <div class="space-y-3">
                            @forelse($tasks ?? [] as $task)
                                <div class="p-3.5 bg-amber-50/50 dark:bg-amber-950/10 border border-amber-100/30 dark:border-amber-900/30 rounded-2xl flex justify-between items-center">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-extrabold text-amber-800 dark:text-amber-400 uppercase tracking-wide">{{ $task['name'] }}</p>
                                        <p class="text-[9px] text-amber-600/80 dark:text-amber-500/80 mt-0.5">Queued {{ \Carbon\Carbon::parse($task['timestamp'])->diffForHumans() }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 rounded text-[9px] font-extrabold uppercase border border-amber-200/20">Pending</span>
                                </div>
                            @empty
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-green-400/80 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 italic">No tasks queued. Device is in sync.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: WAN Settings -->
            <div id="tab-wan" class="tab-content space-y-6 hidden">
                @php
                    $wanList = $wanConnections ?? [];
                    // Find active PPPoE connection
                    $activePppConn = null;
                    foreach ($wanList as $c) {
                        if ($c['is_active_ppp']) {
                            $activePppConn = $c;
                            break;
                        }
                    }
                    if (!$activePppConn && !empty($wanList)) {
                        $activePppConn = $wanList[0];
                    }
                @endphp

                <!-- WAN Interfaces Overview -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-5">
                        <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                            Detected WAN Interfaces ({{ count($wanList) }})
                        </h3>
                        <span class="text-xs text-gray-400 font-medium">TR-069 WAN Data Model</span>
                    </div>

                    @if(!empty($wanList))
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($wanList as $c)
                                <div class="p-4 rounded-2xl border transition-all {{ $c['is_active_ppp'] ? 'bg-indigo-50/40 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800/60 ring-1 ring-indigo-500/20' : 'bg-gray-50 dark:bg-gray-900 border-gray-100/60 dark:border-gray-800' }}">
                                    <div class="flex justify-between items-start mb-2.5">
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $c['protocol'] }} Connection</span>
                                            <h4 class="text-xs font-black text-gray-800 dark:text-gray-200 truncate max-w-[180px]" title="{{ $c['name'] }}">{{ $c['name'] }}</h4>
                                        </div>
                                        @if($c['status'] === 'Connected')
                                            <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded-lg flex items-center gap-1 border border-emerald-200/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Up
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 text-[10px] font-bold rounded-lg">
                                                {{ $c['status'] ?? 'Idle' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="space-y-1 text-xs">
                                        <div class="flex justify-between py-1 border-b border-gray-200/40 dark:border-gray-800/40">
                                            <span class="text-gray-400 text-[11px]">PPPoE User:</span>
                                            <span class="font-mono font-bold text-gray-800 dark:text-gray-200 truncate max-w-[160px]" title="{{ $c['username'] }}">{{ $c['username'] ?: '—' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-gray-200/40 dark:border-gray-800/40">
                                            <span class="text-gray-400 text-[11px]">VLAN ID:</span>
                                            <span class="font-mono font-extrabold text-indigo-600 dark:text-indigo-400">{{ $c['vlan_id'] ?? 'Untagged' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-gray-200/40 dark:border-gray-800/40">
                                            <span class="text-gray-400 text-[11px]">IP Address:</span>
                                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $c['ip'] ?: 'No IP' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1">
                                            <span class="text-gray-400 text-[11px]">Service Type:</span>
                                            <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $c['service_list'] }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 pt-2 border-t border-gray-200/30 dark:border-gray-800/30 text-[10px] text-gray-400 font-mono truncate" title="{{ $c['path'] }}">
                                        {{ $c['path'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                            <p class="text-xs text-gray-500">Belum ada interface WAN terdeteksi dari inform terakhir. Anda tetap dapat memasukkan konfigurasi baru di bawah.</p>
                        </div>
                    @endif
                </div>

                @php
                    $detectedServices = [];
                    foreach ($wanList as $c) {
                        if (!empty($c['service_list'])) {
                            $detectedServices[] = $c['service_list'];
                        }
                    }
                    $commonServices = array_unique(array_merge($detectedServices, [
                        'INTERNET',
                        'TR069,INTERNET',
                        'TR069',
                        'VOIP',
                        'IPTV',
                        'OTHER',
                        'VOIP,INTERNET',
                        'IPTV,INTERNET',
                    ]));
                @endphp

                <!-- WAN PPPoE & Network Configuration Form with Alpine.js Reactivity -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none"
                     x-data="wanConfigForm(@js($wanList), '{{ $activePppConn['path'] ?? '' }}')">
                    
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                                Konfigurasi PPPoE, VLAN & Binding Port
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Parameter TR-069 Asli (Multi-Vendor Direct Mapping tanpa Virtual Parameter)</p>
                        </div>
                        <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold rounded-xl border border-indigo-100 dark:border-indigo-900/40">
                            Pure Native TR-069
                        </span>
                    </div>

                    <!-- Notification alerts inside form -->
                    <div x-show="submitSuccess" x-cloak class="p-4 mb-5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="submitMessage"></span>
                    </div>
                    <div x-show="submitError" x-cloak class="p-4 mb-5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-2xl text-xs flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="submitError"></span>
                    </div>

                    <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" @submit="submitForm($event)" class="space-y-6">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $server->id }}">

                        <!-- Row 1: Target WAN Interface Selector & Status Aktif/Tidak Aktif -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 bg-gray-50/70 dark:bg-gray-900/70 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <!-- Target WAN Interface Selector -->
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    Target WAN Interface
                                </label>
                                <select name="wan_path" x-model="selectedPath" @change="onWanChange()" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    @foreach($wanList as $c)
                                        <option value="{{ $c['path'] }}">
                                            {{ $c['name'] }} — [{{ $c['protocol'] }}] {{ $c['username'] ? 'User: ' . $c['username'] : ($c['service_list'] ?? 'WAN') }} (VLAN: {{ $c['vlan_id'] ?? 'Untagged' }})
                                        </option>
                                    @endforeach
                                    @if(empty($wanList))
                                        <option value="">(Default WAN Connection)</option>
                                    @endif
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Saat memilih interface, semua isian formulir di bawah otomatis menyesuaikan dengan data interface tersebut.</p>
                            </div>

                            <!-- 1. Status Aktif / Tidak Aktif (Enable Parameter) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    Status Interface WAN
                                </label>
                                <div class="relative">
                                    <select name="is_enabled" x-model="isEnabled" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-extrabold outline-none transition-all dark:text-gray-200"
                                            :class="isEnabled == '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <option value="1">🟢 Aktif (Enabled)</option>
                                        <option value="0">🔴 Tidak Aktif (Disabled)</option>
                                    </select>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Mengontrol parameter <code class="font-mono text-[10px] text-indigo-500">Enable</code> koneksi WAN.</p>
                            </div>
                        </div>

                        <!-- Current Selected WAN Telemetry Snippet -->
                        <div x-show="currentConn" class="p-3.5 bg-indigo-50/30 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/40 rounded-2xl text-xs flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase"
                                      :class="currentConn && currentConn.status === 'Connected' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                      x-text="(currentConn ? currentConn.status : 'Unknown')">
                                </span>
                                <span class="text-gray-500 font-mono text-[11px]">IP: <b class="text-gray-800 dark:text-gray-200" x-text="currentConn && currentConn.ip ? currentConn.ip : 'Belum Ada IP'"></b></span>
                            </div>
                            <div class="text-[10px] font-mono text-gray-400 truncate max-w-full" x-text="selectedPath"></div>
                        </div>
                        
                        <!-- Row 2: PPPoE Username & Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">PPPoE Username</label>
                                <input type="text" name="ppp_username" x-model="pppUsername" placeholder="misal: pelanggan@nextlink" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-mono outline-none transition-all dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">PPPoE Password</label>
                                <div class="relative" x-data="{ show: false }">
                                    <input :type="show ? 'text' : 'password'" name="ppp_password" x-model="pppPassword" placeholder="Ketik untuk mengubah password..." class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-mono outline-none transition-all dark:text-gray-200">
                                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: VLAN ID, Connection Trigger, Max MTU/MRU -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">VLAN ID</label>
                                <input type="number" name="vlan_id" x-model="vlanId" placeholder="misal: 101" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-mono font-bold outline-none transition-all dark:text-gray-200">
                                <p class="text-[10px] text-gray-400 mt-1">Otomatis dipetakan ke X_HW_VLAN / X_ZTE-COM_VLANID / VLANIDMark</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Connection Trigger</label>
                                <select name="connection_trigger" x-model="connectionTrigger" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    <option value="AlwaysOn">AlwaysOn (Default)</option>
                                    <option value="OnDemand">OnDemand</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Max MRU / MTU</label>
                                <input type="number" name="mru" x-model="mru" placeholder="1492" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-mono outline-none transition-all dark:text-gray-200">
                            </div>
                        </div>

                        <!-- Row 4: Service Type & NAT Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 4. Service Type Optional & Detected from Modem -->
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    Service Type (Tipe Layanan)
                                </label>
                                <div class="space-y-2">
                                    <select x-model="serviceType" @change="onServiceSelect($event.target.value)" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                        @foreach($commonServices as $svc)
                                            <option value="{{ $svc }}">{{ $svc }} {{ in_array($svc, $detectedServices) ? '⭐ (Ada di modem)' : '' }}</option>
                                        @endforeach
                                        <option value="__CUSTOM__">✏️ Tulis Tipe Sendiri (Kustom)...</option>
                                    </select>
                                    
                                    <!-- Custom Service Text Input (if custom selected) -->
                                    <div x-show="isCustomService" x-cloak>
                                        <input type="text" name="service_list_custom" x-model="customServiceType" placeholder="Ketik service type (misal: OTHER_VOIP)..." class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-mono outline-none transition-all dark:text-gray-200">
                                    </div>
                                    <input type="hidden" name="service_list" :value="isCustomService ? customServiceType : serviceType">
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Membaca opsi umum modem serta service yang terdeteksi pada ONT saat ini.</p>
                            </div>

                            <!-- NAT Status -->
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">NAT Status</label>
                                <select name="nat_enabled" x-model="natEnabled" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    <option value="1">Enabled (Disarankan)</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Network Address Translation untuk koneksi WAN Internet.</p>
                            </div>
                        </div>

                        <!-- 3. Local Interface Binding (LAN & Wi-Fi SSIDs) -->
                        <div class="p-5 bg-gray-50/60 dark:bg-gray-900/60 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-4">
                            <div>
                                <h4 class="text-xs font-extrabold text-gray-700 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    Bind Interface Local (Port Binding)
                                </h4>
                                <p class="text-[11px] text-gray-400 mt-0.5">Pilih port LAN fisik dan Wireless SSID mana saja yang dibinding langsung ke interface WAN ini.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- LAN Ports Binding -->
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700/80">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                        <span>🔌</span> Ethernet LAN Ports
                                    </p>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <template x-for="port in [1, 2, 3, 4]" :key="'lan' + port">
                                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-bold"
                                                   :class="lanBind['lan' + port] ? 'bg-indigo-50/80 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-gray-50/50 dark:bg-gray-900/50 border-gray-200/60 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100/50'">
                                                <input type="checkbox" :name="'lan_bind[lan' + port + ']'" value="1"
                                                       x-model="lanBind['lan' + port]"
                                                       class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                                <span x-text="'LAN ' + port"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <!-- Wi-Fi SSIDs Binding -->
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700/80">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                        <span>📡</span> Wireless SSIDs
                                    </p>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <template x-for="ssid in [1, 2, 3, 4]" :key="'ssid' + ssid">
                                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-bold"
                                                   :class="ssidBind['ssid' + ssid] ? 'bg-indigo-50/80 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-gray-50/50 dark:bg-gray-900/50 border-gray-200/60 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100/50'">
                                                <input type="checkbox" :name="'ssid_bind[ssid' + ssid + ']'" value="1"
                                                       x-model="ssidBind['ssid' + ssid]"
                                                       class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                                <span x-text="'SSID ' + ssid"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Enhanced Push Button with Spinner & Disabled State -->
                        <div class="pt-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <button type="submit"
                                    :disabled="submitting"
                                    class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-2xl font-bold text-xs transition-all shadow-lg shadow-indigo-500/20 flex items-center justify-center gap-2.5 active:scale-95 cursor-pointer disabled:cursor-not-allowed">
                                <!-- Loading Spinner -->
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <!-- Send Icon -->
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                <span x-text="submitting ? 'Mengirim ke ONT (GenieACS)...' : 'Push Konfigurasi PPPoE ke ONT'"></span>
                            </button>
                            <span class="text-[11px] text-gray-400 text-center sm:text-right">
                                Direct RPC: Flash Save ONT + Wakeup Connection Request
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 4: Wi-Fi Configuration -->
            <div id="tab-wireless" class="tab-content space-y-6 hidden">
                <!-- Wireless Management Panels (WLAN Configuration) -->
                @for($i = 1; $i <= 4; $i++)
                    @php
                        $igdWlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$i] ?? null;
                        $devWlanSsid = $device['Device']['WiFi']['SSID'][$i] ?? null;
                        $devWlanRadio = $device['Device']['WiFi']['Radio'][$i] ?? null;
                        $devWlanAp = $device['Device']['WiFi']['AccessPoint'][$i] ?? null;

                        if (!$igdWlan && !$devWlanSsid) continue;

                        $ssid = $igdWlan['SSID']['_value'] ?? $devWlanSsid['SSID']['_value'] ?? 'Unknown';
                        $band = $devWlanRadio['OperatingFrequencyBand']['_value'] ?? ($i == 2 ? '5 GHz' : '2.4 GHz');
                        $wlanEnabled = $igdWlan['Enable']['_value'] ?? $devWlanRadio['Enable']['_value'] ?? null;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                                Wireless SSID Profile #{{ $i }} ({{ $band }})
                            </h3>
                            @if($wlanEnabled === '1' || $wlanEnabled === true || $wlanEnabled === 'true')
                                <span class="px-2.5 py-1 bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 rounded-lg text-[10px] font-extrabold uppercase border border-green-100/20 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    SSID Active
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-lg text-[10px] font-extrabold uppercase border border-gray-100/30">
                                    SSID Off
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-[9px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-0.5">Active SSID Name</p>
                                <p class="text-xs font-extrabold text-indigo-600 dark:text-indigo-400">{{ $ssid }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-[9px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-0.5">RF Channel</p>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $igdWlan['Channel']['_value'] ?? $devWlanRadio['Channel']['_value'] ?? 'Auto' }}
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-[9px] text-gray-400 dark:text-gray-500 uppercase font-bold mb-0.5">Authentication Mode</p>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                    {{ $igdWlan['BeaconType']['_value'] ?? $devWlanAp['Security']['ModeEnabled']['_value'] ?? 'WPA2-PSK' }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                            <input type="hidden" name="instance" value="{{ $i }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Change SSID Name</label>
                                    <input type="text" name="wifi_ssid" value="{{ $ssid }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Change Wi-Fi Password</label>
                                    <input type="password" name="wifi_password" placeholder="Leave blank to keep unchanged" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200">
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-xs transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-2 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                @endfor
            </div>
        </div>

        <!-- Quick Actions Panel at the bottom -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none mt-6">
            <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                Quick Actions
            </h3>
            <div class="flex flex-wrap gap-3">
                <form action="{{ route('acs-servers.reboot', ['deviceId' => $deviceId]) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="server_id" value="{{ $server->id }}">
                    <button type="submit" class="px-6 py-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-2xl text-xs font-bold transition-all border border-rose-100/30 dark:border-rose-900/30 active:scale-95 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Reboot CPE
                    </button>
                </form>
                <button onclick="triggerAcsSync(event, '{{ route('acs-servers.refresh-device', ['deviceId' => $deviceId]) }}', '{{ $server->id }}', '{{ csrf_token() }}')" class="px-6 py-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl text-xs font-bold transition-all border border-indigo-100/30 dark:border-indigo-900/30 active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5"></path></svg>
                    Summon
                </button>
            </div>
        </div>

        <!-- Toast Notifications Container -->
        <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-md"></div>

        <script>
            function showToast(message, type) {
                type = type || 'info';
                var container = document.getElementById('toast-container');
                if (!container) return;
                
                var toast = document.createElement('div');
                toast.className = "flex items-center gap-3 px-5 py-3.5 rounded-2xl border text-sm font-semibold shadow-lg transition-all duration-300 transform translate-y-2 opacity-0";
                
                var bg, border, text, icon;
                if (type === 'success') {
                    bg = 'bg-green-50 dark:bg-green-950/90';
                    border = 'border-green-100 dark:border-green-900';
                    text = 'text-green-800 dark:text-green-400';
                    icon = '<svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                } else if (type === 'error') {
                    bg = 'bg-rose-50 dark:bg-rose-950/90';
                    border = 'border-rose-100 dark:border-rose-900';
                    text = 'text-rose-800 dark:text-rose-400';
                    icon = '<svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                } else {
                    bg = 'bg-indigo-50 dark:bg-indigo-950/90';
                    border = 'border-indigo-100 dark:border-indigo-900';
                    text = 'text-indigo-800 dark:text-indigo-400';
                    icon = '<svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                }
                
                toast.className += ' ' + bg + ' ' + border + ' ' + text;
                toast.innerHTML = icon + '<span>' + message + '</span>';
                container.appendChild(toast);
                
                setTimeout(function() {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                }, 10);
                
                setTimeout(function() {
                    toast.classList.add('opacity-0', 'translate-y-1');
                    setTimeout(function() { toast.remove(); }, 300);
                }, 5000);
            }

            function triggerAcsSync(event, url, serverId, csrfToken) {
                if (event) event.preventDefault();
                
                showToast("Memulai penyelarasan ulang data CPE...", "info");
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        server_id: serverId
                    })
                })
                .then(function(response) {
                    if (!response.ok) throw new Error("HTTP error " + response.status);
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showToast("Perintah refresh berhasil dikirim. Sinkronisasi berjalan di latar belakang.", "success");
                    } else {
                        showToast("Gagal me-refresh: " + data.message, "error");
                    }
                })
                .catch(function(error) {
                    console.error(error);
                    showToast("Gagal menghubungi server GenieACS untuk refresh.", "error");
                });
            }

            function switchTab(tabId) {
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(function(el) {
                    el.classList.add('hidden');
                });
                
                // Show selected tab content
                document.getElementById(tabId).classList.remove('hidden');
                
                // Reset all tab buttons to inactive
                document.querySelectorAll('.tab-btn').forEach(function(btn) {
                    btn.classList.remove('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-500/10');
                    btn.classList.add('text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-300');
                });
                
                // Set clicked tab button to active
                var activeBtn = document.getElementById('btn-' + tabId);
                activeBtn.classList.remove('text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-300');
                activeBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-500/10');
            }

            function wanConfigForm(wanList, defaultPath) {
                return {
                    wanList: wanList || [],
                    selectedPath: defaultPath || (wanList && wanList.length > 0 ? wanList[0].path : ''),
                    currentConn: null,
                    isEnabled: '1',
                    pppUsername: '',
                    pppPassword: '',
                    vlanId: '',
                    connectionTrigger: 'AlwaysOn',
                    mru: '1492',
                    serviceType: 'INTERNET',
                    customServiceType: '',
                    isCustomService: false,
                    natEnabled: '1',
                    lanBind: { lan1: false, lan2: false, lan3: false, lan4: false },
                    ssidBind: { ssid1: false, ssid2: false, ssid3: false, ssid4: false },
                    submitting: false,
                    submitSuccess: false,
                    submitMessage: '',
                    submitError: '',

                    init() {
                        this.onWanChange();
                    },

                    onWanChange() {
                        var self = this;
                        var found = this.wanList.find(function(c) { return c.path === self.selectedPath; });
                        if (found) {
                            this.currentConn = found;
                            this.isEnabled = (found.enable !== undefined && found.enable !== null) ? String(found.enable) : '1';
                            this.pppUsername = found.username || '';
                            this.pppPassword = '';
                            this.vlanId = (found.vlan_id !== undefined && found.vlan_id !== null) ? String(found.vlan_id) : '';
                            this.connectionTrigger = found.connection_trigger || 'AlwaysOn';
                            this.mru = found.mru || '1492';
                            this.natEnabled = (found.nat_enabled !== undefined && found.nat_enabled !== null) ? String(found.nat_enabled) : '1';
                            
                            var serv = found.service_list || 'INTERNET';
                            this.serviceType = serv;
                            this.customServiceType = '';
                            this.isCustomService = false;

                            if (found.lan_bind) {
                                this.lanBind = {
                                    lan1: !!found.lan_bind.lan1,
                                    lan2: !!found.lan_bind.lan2,
                                    lan3: !!found.lan_bind.lan3,
                                    lan4: !!found.lan_bind.lan4
                                };
                            } else {
                                this.lanBind = { lan1: false, lan2: false, lan3: false, lan4: false };
                            }

                            if (found.ssid_bind) {
                                this.ssidBind = {
                                    ssid1: !!found.ssid_bind.ssid1,
                                    ssid2: !!found.ssid_bind.ssid2,
                                    ssid3: !!found.ssid_bind.ssid3,
                                    ssid4: !!found.ssid_bind.ssid4
                                };
                            } else {
                                this.ssidBind = { ssid1: false, ssid2: false, ssid3: false, ssid4: false };
                            }
                        }
                    },

                    onServiceSelect(val) {
                        if (val === '__CUSTOM__') {
                            this.isCustomService = true;
                            if (!this.customServiceType) {
                                this.customServiceType = 'INTERNET';
                            }
                        } else {
                            this.isCustomService = false;
                            this.serviceType = val;
                        }
                    },

                    submitForm(e) {
                        e.preventDefault();
                        if (this.submitting) return;

                        var self = this;
                        self.submitting = true;
                        self.submitError = '';
                        self.submitMessage = '';
                        self.submitSuccess = false;

                        var form = e.target;
                        var formData = new FormData(form);

                        if (self.isCustomService && self.customServiceType) {
                            formData.set('service_list', self.customServiceType);
                        }

                        fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return { ok: res.ok, status: res.status, data: data };
                            }).catch(function() {
                                return { ok: res.ok, status: res.status, data: {} };
                            });
                        })
                        .then(function(result) {
                            self.submitting = false;
                            if (result.ok && result.data.success) {
                                self.submitSuccess = true;
                                self.submitMessage = result.data.message || 'Konfigurasi PPPoE/Jaringan berhasil dikirim ke ONT.';
                                showToast(self.submitMessage, 'success');
                                if (self.currentConn) {
                                    self.currentConn.username = self.pppUsername;
                                    self.currentConn.vlan_id = self.vlanId;
                                    self.currentConn.enable = self.isEnabled;
                                    self.currentConn.service_list = self.isCustomService ? self.customServiceType : self.serviceType;
                                }
                            } else {
                                self.submitSuccess = false;
                                self.submitError = (result.data && result.data.message) ? result.data.message : 'Gagal mengirim konfigurasi.';
                                showToast(self.submitError, 'error');
                            }
                        })
                        .catch(function(err) {
                            self.submitting = false;
                            self.submitSuccess = false;
                            self.submitError = 'Gagal menghubungi server: ' + err.message;
                            showToast(self.submitError, 'error');
                        });
                    }
                };
            }
        </script>
    @endif
</div>
</x-app-layout>
