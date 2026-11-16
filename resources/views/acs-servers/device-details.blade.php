<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('acs-servers.devices', ['server_id' => $server->id]) }}" class="btn-ghost btn-icon" aria-label="Kembali ke daftar modem">
                    <x-icon name="chevron-right" class="w-5 h-5 rotate-180" />
                </a>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight truncate">
                            {{ $summary['model'] ?? 'Modem' }}@if(! empty($summary['manufacturer']))<span class="font-normal text-surface-400"> · {{ $summary['manufacturer'] }}</span>@endif
                        </h2>
                        @if($summary)
                            <span class="{{ $summary['online'] ? 'badge-success' : 'badge-gray' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $summary['online'] ? 'bg-emerald-500 animate-pulse' : 'bg-surface-400' }}"></span>
                                {{ $summary['online'] ? 'Online' : 'Offline' }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-surface-500 dark:text-surface-400 truncate">
                        <span class="font-mono">{{ $summary['serial'] ?? $deviceId }}</span>
                        @if(! empty($summary['customer']))
                            · <a href="{{ route('customers.show', $summary['customer']->id) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $summary['customer']->name }}</a>
                        @endif
                    </p>
                </div>
            </div>
            @if($device ?? null)
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('acs-servers.show-device', ['deviceId' => $deviceId, 'server_id' => $server->id]) }}" target="_blank" class="btn-ghost btn-sm">Data mentah</a>
                    <button type="button" class="btn-secondary" onclick="triggerAcsSync(event, @js(route('acs-servers.refresh-device', ['deviceId' => $deviceId])), @js((string) $server->id), @js(csrf_token()))">
                        <x-icon name="refresh" class="w-4 h-4" /> Sinkron data
                    </button>
                    @canroute('acs-servers.reboot')
                        <form action="{{ route('acs-servers.reboot', ['deviceId' => $deviceId]) }}" method="POST" onsubmit="return confirm('Reboot modem ini? Internet pelanggan terputus ±1–2 menit.')">
                            @csrf
                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                            <button class="btn-danger"><x-icon name="bolt" class="w-4 h-4" /> Reboot</button>
                        </form>
                    @endcanroute
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900 text-green-700 dark:text-green-400 rounded-xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-400 rounded-xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if(isset($error))
            <div class="p-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-md text-center max-w-lg mx-auto">
                <svg class="w-12 h-12 text-rose-500 mx-auto mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2">Modem tidak bisa dibaca</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ $error }}</p>
                <a href="javascript:history.back()" class="inline-flex px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10">
                    Kembali ke daftar modem
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

        @php
            $sv = fn (array $paths) => \App\Support\AcsDevice::first($device, $paths);
            $uptimeSeconds = (int) ($sv(['InternetGatewayDevice.DeviceInfo.UpTime', 'Device.DeviceInfo.UpTime']) ?? 0);
            $uptime = $uptimeSeconds ? (floor($uptimeSeconds / 86400) ? floor($uptimeSeconds / 86400) . ' hari ' . floor(($uptimeSeconds % 86400) / 3600) . ' jam'
                : (floor($uptimeSeconds / 3600) ? floor($uptimeSeconds / 3600) . ' jam ' . floor(($uptimeSeconds % 3600) / 60) . ' menit' : floor($uptimeSeconds / 60) . ' menit')) : null;
            $level = \App\Support\AcsDevice::signalLevel($summary['rx']);
            $sigCls = ['good' => 'text-emerald-600 dark:text-emerald-400', 'warn' => 'text-amber-600 dark:text-amber-400', 'bad' => 'text-rose-600 dark:text-rose-400', 'none' => 'text-surface-400'][$level];
            $sigBar = ['good' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'bad' => 'bg-rose-500', 'none' => 'bg-surface-300'][$level];
            $info = array_filter([
                'Merek' => $summary['manufacturer'] ?: null,
                'Model' => $summary['model'] ?: null,
                'Nomor seri' => $summary['serial'] ?: null,
                'Firmware' => $sv(['InternetGatewayDevice.DeviceInfo.SoftwareVersion', 'DeviceID.SoftwareVersion', 'Device.DeviceInfo.SoftwareVersion']),
                'Versi hardware' => $sv(['InternetGatewayDevice.DeviceInfo.HardwareVersion', 'Device.DeviceInfo.HardwareVersion']),
                'Username PPPoE' => $summary['pppoe'],
                'ID GenieACS' => $deviceId,
                'Server ACS' => $server->name,
            ], fn ($v) => $v !== null && $v !== '');
        @endphp

        {{-- Ringkasan cepat --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Inform terakhir</p>
                <p class="mt-1 text-lg font-semibold text-surface-900 dark:text-white">{{ $summary['last_inform'] ? $summary['last_inform']->locale('id')->diffForHumans() : 'Belum pernah' }}</p>
                <p class="text-xs text-surface-500">{{ $summary['last_inform']?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Sinyal Rx</p>
                <p class="mt-1 text-lg font-semibold tabular-nums {{ $sigCls }}">{{ $summary['rx'] !== null ? number_format($summary['rx'], 2) . ' dBm' : '—' }}</p>
                @if($summary['rx'] !== null)
                    <div class="mt-1.5 h-1.5 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                        <div class="h-full rounded-full {{ $sigBar }}" style="width: {{ max(5, min(100, (($summary['rx'] + 35) / 27) * 100)) }}%"></div>
                    </div>
                @endif
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">IP WAN</p>
                <p class="mt-1 text-lg font-semibold font-mono text-surface-900 dark:text-white truncate">{{ $summary['ip'] ?: '—' }}</p>
                <p class="text-xs text-surface-500 truncate">{{ $summary['pppoe'] ?: 'PPPoE tidak terbaca' }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Waktu aktif modem</p>
                <p class="mt-1 text-lg font-semibold text-surface-900 dark:text-white">{{ $uptime ?? '—' }}</p>
                <p class="text-xs text-surface-500">sejak modem terakhir menyala</p>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Tab --}}
            <div class="border-b border-surface-200 dark:border-surface-800">
                <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Bagian detail modem">
                    @foreach(['tab-overview' => 'Ringkasan', 'tab-diagnostics' => 'Optik & perangkat terhubung', 'tab-wan' => 'WAN / PPPoE', 'tab-wireless' => 'Wi-Fi'] as $tabId => $tabLabel)
                        <button type="button" onclick="switchTab('{{ $tabId }}')" id="btn-{{ $tabId }}"
                                class="tab-btn whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors {{ $loop->first ? 'border-primary-600 text-primary-700 dark:text-primary-300' : 'border-transparent text-surface-500 hover:text-surface-800 dark:hover:text-surface-200' }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Tab 1: Ringkasan --}}
            <div id="tab-overview" class="tab-content grid grid-cols-1 gap-6 lg:grid-cols-3">
                <section class="card lg:col-span-2">
                    <div class="card-header"><h3 class="card-title">Info perangkat</h3></div>
                    <dl class="divide-y divide-surface-100 dark:divide-surface-800 text-sm">
                        @foreach($info as $k => $v)
                            <div class="flex justify-between gap-4 px-5 py-2.5">
                                <dt class="text-surface-500">{{ $k }}</dt>
                                <dd class="text-right font-medium text-surface-900 dark:text-white break-all {{ in_array($k, ['Nomor seri', 'ID GenieACS', 'Username PPPoE', 'Firmware']) ? 'font-mono text-xs' : '' }}">{{ $v }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
                <section class="card">
                    <div class="card-header"><h3 class="card-title">Pelanggan</h3></div>
                    <div class="card-body text-sm">
                        @if($summary['customer'])
                            <p class="font-semibold text-surface-900 dark:text-white">{{ $summary['customer']->name }}</p>
                            <p class="font-mono text-xs text-surface-500">{{ $summary['customer']->customer_code }} · {{ $summary['customer']->username }}</p>
                            @canroute('customers.show')
                                <a href="{{ route('customers.show', $summary['customer']->id) }}" class="btn-secondary btn-sm mt-3">Buka data pelanggan</a>
                            @endcanroute
                        @else
                            <p class="text-surface-500">Belum tertaut ke pelanggan.</p>
                            <p class="mt-1 text-xs text-surface-400">Modem ditautkan otomatis bila username PPPoE di modem sama dengan username pelanggan.</p>
                        @endif
                        @if($summary['tags'])
                            <p class="mt-4 text-xs font-medium text-surface-500">Tag</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($summary['tags'] as $tag)
                                    <a href="{{ route('acs-servers.devices', ['server_id' => $server->id, 'tag' => $tag]) }}" class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[11px] font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300">{{ $tag }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <!-- Tab 2: Diagnostics & Clients -->
            <div id="tab-diagnostics" class="tab-content space-y-6 hidden">
                <!-- Optical Transceiver Diagnostics Card -->
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-surface-900 dark:text-white mb-5 flex items-center gap-2">
                        Optik (GPON/EPON)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-bold tracking-wider mb-1">Daya Optik Rx</p>
                            @if($rxPower !== null)
                                @php
                                    $colorClass = 'text-green-600 dark:text-green-400';
                                    if ($rxPower < -27) {
                                        $colorClass = 'text-rose-600 dark:text-rose-400 font-semibold animate-pulse';
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
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-bold tracking-wider mb-1">Daya Optik Tx</p>
                            @if($txPower !== null)
                                <p class="text-base font-mono font-extrabold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($txPower, 2) }} dBm
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-bold tracking-wider mb-1">Suhu transceiver</p>
                            @if($temperature !== null)
                                <p class="text-base font-mono font-extrabold text-gray-750 dark:text-gray-300">
                                    {{ number_format($temperature, 1) }} °C
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-bold tracking-wider mb-1">Arus Bias Laser</p>
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
                    <div class="card p-6">
                        @php
                            $hostCount = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['HostNumberOfEntries']['_value']
                                ?? $device['Device']['Hosts']['HostNumberOfEntries']['_value']
                                ?? 0;
                        @endphp
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-base font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                                Perangkat WLAN Aktif
                            </h3>
                            <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-extrabold">
                                {{ $hostCount }} terhubung
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
                                    <div class="p-3 bg-gray-50/50 dark:bg-gray-900/50 rounded-xl border border-gray-100/50 dark:border-gray-800 flex justify-between items-center">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                                {{ $host['HostName']['_value'] ?? 'Unnamed Client' }}
                                            </p>
                                            <p class="text-xs font-mono text-gray-400 mt-0.5">{{ $host['IPAddress']['_value'] ?? 'No IP' }} • {{ $host['MACAddress']['_value'] ?? 'No MAC' }}</p>
                                        </div>
                                        <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 rounded-md border border-green-100/30">Aktif</span>
                                    </div>
                                @endif
                            @endfor

                            @if(!$foundHosts)
                                <div class="text-center py-8">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <p class="text-xs text-gray-400 italic">Tidak ada perangkat terhubung.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- ACS Task Queue -->
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-surface-900 dark:text-white mb-4 flex items-center gap-2">
                            Antrean Tugas
                        </h3>
                        <div class="space-y-3">
                            @forelse($tasks ?? [] as $task)
                                <div class="p-3.5 bg-amber-50/50 dark:bg-amber-950/10 border border-amber-100/30 dark:border-amber-900/30 rounded-xl flex justify-between items-center">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-extrabold text-amber-800 dark:text-amber-400 uppercase tracking-wide">{{ $task['name'] }}</p>
                                        <p class="text-xs text-amber-600/80 dark:text-amber-500/80 mt-0.5">Queued {{ \Carbon\Carbon::parse($task['timestamp'])->diffForHumans() }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 rounded text-xs font-extrabold border border-amber-200/20">Menunggu</span>
                                </div>
                            @empty
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-green-400/80 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 italic">Tidak ada antrean. Perangkat sudah sinkron.</p>
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
                <div class="card p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-5">
                        <h3 class="text-base font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                            Interface WAN terdeteksi ({{ count($wanList) }})
                        </h3>
                        <span class="text-xs text-gray-400 font-medium">Interface aktif ditandai</span>
                    </div>

                    @if(!empty($wanList))
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($wanList as $c)
                                <div class="p-4 rounded-xl border transition-all {{ $c['is_active_ppp'] ? 'bg-indigo-50/40 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800/60 ring-1 ring-indigo-500/20' : 'bg-gray-50 dark:bg-gray-900 border-gray-100/60 dark:border-gray-800' }}">
                                    <div class="flex justify-between items-start mb-2.5">
                                        <div>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $c['protocol'] }} Connection</span>
                                            <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[180px]" title="{{ $c['name'] }}">{{ $c['name'] }}</h4>
                                        </div>
                                        @if($c['status'] === 'Connected')
                                            <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold rounded-lg flex items-center gap-1 border border-emerald-200/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Up
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 text-xs font-bold rounded-lg">
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
                                            <span class="text-gray-400 text-[11px]">Alamat IP:</span>
                                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $c['ip'] ?: 'No IP' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1">
                                            <span class="text-gray-400 text-[11px]">Tipe Layanan:</span>
                                            <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $c['service_list'] }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 pt-2 border-t border-gray-200/30 dark:border-gray-800/30 text-xs text-gray-400 font-mono truncate" title="{{ $c['path'] }}">
                                        {{ $c['path'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
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
                <div class="card p-6"
                     x-data="wanConfigForm(@js($wanList), '{{ $activePppConn['path'] ?? '' }}')">
                    
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-base font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                                Konfigurasi PPPoE, VLAN & Binding Port
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Perubahan dikirim langsung ke modem lewat TR-069 dan tercatat di Log Aktivitas.</p>
                        </div>
                    </div>

                    <!-- Notification alerts inside form -->
                    <div x-show="submitSuccess" x-cloak class="p-4 mb-5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="submitMessage"></span>
                    </div>
                    <div x-show="submitError" x-cloak class="p-4 mb-5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="submitError"></span>
                    </div>

                    <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" @submit="submitForm($event)" class="space-y-6">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $server->id }}">

                        <!-- Row 1: Target WAN Interface Selector & Status Aktif/Tidak Aktif -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 bg-gray-50/70 dark:bg-gray-900/70 rounded-xl border border-gray-100 dark:border-gray-800">
                            <!-- Target WAN Interface Selector -->
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    Interface WAN Tujuan
                                </label>
                                <select name="wan_path" x-model="selectedPath" @change="onWanChange()" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    @foreach($wanList as $c)
                                        <option value="{{ $c['path'] }}">
                                            {{ $c['name'] }} — [{{ $c['protocol'] }}] {{ $c['username'] ? 'User: ' . $c['username'] : ($c['service_list'] ?? 'WAN') }} (VLAN: {{ $c['vlan_id'] ?? 'Untagged' }})
                                        </option>
                                    @endforeach
                                    @if(empty($wanList))
                                        <option value="">(Koneksi WAN Default)</option>
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
                                    <select name="is_enabled" x-model="isEnabled" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-extrabold outline-none transition-all dark:text-gray-200"
                                            :class="isEnabled == '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        <option value="1">🟢 Aktif (Enabled)</option>
                                        <option value="0">🔴 Tidak Aktif (Disabled)</option>
                                    </select>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Mengontrol parameter <code class="font-mono text-xs text-indigo-500">Aktifkan</code> koneksi WAN.</p>
                            </div>
                        </div>

                        <!-- Current Selected WAN Telemetry Snippet -->
                        <div x-show="currentConn" class="p-3.5 bg-indigo-50/30 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/40 rounded-xl text-xs flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-0.5 rounded text-xs font-mono"
                                      :class="currentConn && currentConn.status === 'Connected' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                      x-text="(currentConn ? currentConn.status : 'Unknown')">
                                </span>
                                <span class="text-gray-500 font-mono text-[11px]">IP: <b class="text-gray-800 dark:text-gray-200" x-text="currentConn && currentConn.ip ? currentConn.ip : 'Belum Ada IP'"></b></span>
                            </div>
                            <div class="text-xs font-mono text-gray-400 truncate max-w-full" x-text="selectedPath"></div>
                        </div>
                        
                        <!-- Row 2: PPPoE Username & Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">PPPoE Username</label>
                                <input type="text" name="ppp_username" x-model="pppUsername" placeholder="misal: pelanggan@nextlink" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono outline-none transition-all dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">PPPoE Password</label>
                                <div class="relative" x-data="{ show: false }">
                                    <input :type="show ? 'text' : 'password'" name="ppp_password" x-model="pppPassword" placeholder="Ketik untuk mengubah password..." class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono outline-none transition-all dark:text-gray-200">
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
                                <input type="number" name="vlan_id" x-model="vlanId" placeholder="misal: 101" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono font-bold outline-none transition-all dark:text-gray-200">
                                <p class="text-xs text-gray-400 mt-1">Otomatis dipetakan ke X_HW_VLAN / X_ZTE-COM_VLANID / VLANIDMark</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Connection Trigger</label>
                                <select name="connection_trigger" x-model="connectionTrigger" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    <option value="AlwaysOn">AlwaysOn (Default)</option>
                                    <option value="OnDemand">OnDemand</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Max MRU / MTU</label>
                                <input type="number" name="mru" x-model="mru" placeholder="1492" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-mono outline-none transition-all dark:text-gray-200">
                            </div>
                        </div>

                        <!-- Row 4: Service Type & Status NAT -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 4. Service Type Optional & Detected from Modem -->
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    Service Type (Tipe Layanan)
                                </label>
                                <div class="space-y-2">
                                    <select x-model="serviceType" @change="onServiceSelect($event.target.value)" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
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

                            <!-- Status NAT -->
                            <div>
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Status NAT</label>
                                <select name="nat_enabled" x-model="natEnabled" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm font-semibold outline-none transition-all dark:text-gray-200">
                                    <option value="1">Enabled (Disarankan)</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Network Address Translation untuk koneksi WAN Internet.</p>
                            </div>
                        </div>

                        <!-- 3. Local Interface Binding (LAN & Wi-Fi SSIDs) -->
                        <div class="p-5 bg-gray-50/60 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-800 space-y-4">
                            <div>
                                <h4 class="text-xs font-extrabold text-gray-700 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    Binding Interface Lokal (Port)
                                </h4>
                                <p class="text-[11px] text-gray-400 mt-0.5">Pilih port LAN fisik dan Wireless SSID mana saja yang dibinding langsung ke interface WAN ini.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- LAN Ports Binding -->
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700/80">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                        <span>🔌</span> Port LAN Ethernet
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
                                    class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-xl font-bold text-xs transition-all shadow-sm flex items-center justify-center gap-2.5  cursor-pointer disabled:cursor-not-allowed">
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
                                RPC Langsung: Simpan ke ONT + Connection Request
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

                    <div class="card p-6">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-base font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                                Profil Wi-Fi #{{ $i }} ({{ $band }})
                            </h3>
                            @if($wlanEnabled === '1' || $wlanEnabled === true || $wlanEnabled === 'true')
                                <span class="px-2.5 py-1 bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 rounded-lg text-xs font-extrabold border border-green-100/20 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    SSID Aktif
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-lg text-xs font-extrabold border border-gray-100/30">
                                    SSID Off
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-xs text-gray-400 dark:text-gray-500 font-bold mb-0.5">Nama SSID Aktif</p>
                                <p class="text-xs font-extrabold text-indigo-600 dark:text-indigo-400">{{ $ssid }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-xs text-gray-400 dark:text-gray-500 font-bold mb-0.5">RF Channel</p>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $igdWlan['Channel']['_value'] ?? $devWlanRadio['Channel']['_value'] ?? 'Auto' }}
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100/50 dark:border-gray-800">
                                <p class="text-xs text-gray-400 dark:text-gray-500 font-bold mb-0.5">Authentication Mode</p>
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
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Ubah Nama SSID</label>
                                    <input type="text" name="wifi_ssid" value="{{ $ssid }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm outline-none transition-all dark:text-gray-200">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Change Wi-Fi Password</label>
                                    <input type="password" name="wifi_password" placeholder="Leave blank to keep unchanged" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm outline-none transition-all dark:text-gray-200">
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-2 ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                @endfor
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
                toast.className = "flex items-center gap-3 px-5 py-3.5 rounded-xl border text-sm font-semibold shadow-lg transition-all duration-300 transform translate-y-2 opacity-0";
                
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
                
                var on = ['border-primary-600', 'text-primary-700', 'dark:text-primary-300'];
                var off = ['border-transparent', 'text-surface-500', 'hover:text-surface-800', 'dark:hover:text-surface-200'];
                document.querySelectorAll('.tab-btn').forEach(function(btn) {
                    btn.classList.remove.apply(btn.classList, on);
                    btn.classList.add.apply(btn.classList, off);
                });
                var activeBtn = document.getElementById('btn-' + tabId);
                activeBtn.classList.remove.apply(activeBtn.classList, off);
                activeBtn.classList.add.apply(activeBtn.classList, on);
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
