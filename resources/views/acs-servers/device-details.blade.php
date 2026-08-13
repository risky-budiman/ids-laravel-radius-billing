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
                <!-- WAN Status Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                        Network WAN Status
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">WAN IP Address</p>
                            <span class="inline-block px-3 py-1 bg-green-50 dark:bg-green-950/20 border border-green-100/30 text-green-700 dark:text-green-400 rounded-xl text-sm font-mono font-extrabold">
                                {{ $ip }}
                            </span>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-1">Active PPPoE Username</p>
                            <p class="text-sm font-mono font-extrabold text-gray-700 dark:text-gray-300">
                                {{ $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['Username']['_value'] 
                                   ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['Username']['_value']
                                   ?? $device['Device']['PPP']['Interface'][1]['Username']['_value']
                                   ?? $device['VirtualParameters']['pppUsername']['_value'] 
                                   ?? 'Not Configured / Static' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- WAN PPPoE & Network Configuration Form -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none">
                    <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-100 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                        WAN PPPoE & Network Configuration
                    </h3>
                    <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $server->id }}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">New PPPoE Username</label>
                                <input type="text" name="ppp_username" value="{{ $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['Username']['_value'] ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['Username']['_value'] ?? $device['Device']['PPP']['Interface'][1]['Username']['_value'] ?? $device['VirtualParameters']['pppUsername']['_value'] ?? '' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">New PPPoE Password</label>
                                <input type="password" name="ppp_password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Configure VLAN ID</label>
                            @php
                                $currentVlan = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][2]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalPBITest']['_value']
                                    ?? $device['Device']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['X_HW_VLAN']['_value']
                                    ?? null;
                            @endphp
                            <div class="mb-2">
                                <span class="text-[10px] text-gray-400">Current VLAN ID:</span>
                                <span class="text-xs font-mono font-extrabold text-indigo-600 dark:text-indigo-400">{{ $currentVlan ?? 'Not Found / Untagged' }}</span>
                            </div>
                            <input type="number" name="vlan_id" value="{{ $currentVlan }}" placeholder="e.g. 100" class="w-full md:w-1/2 px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full md:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-xs transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-2 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Save
                            </button>
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
        </script>
    @endif
</div>
</x-app-layout>
