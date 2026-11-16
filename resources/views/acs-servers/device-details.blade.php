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

            {{-- Tab 2: Optik & perangkat terhubung --}}
            @php
                $lbl = 'block text-sm font-medium text-surface-700 dark:text-surface-300';
                $hint = 'mt-1 text-xs text-surface-500 dark:text-surface-400';
                $hostsRaw = $device['InternetGatewayDevice']['LANDevice'][1]['Hosts']['Host'] ?? $device['Device']['Hosts']['Host'] ?? [];
                $hosts = collect(is_array($hostsRaw) ? $hostsRaw : [])
                    ->filter(fn ($h, $k) => is_numeric($k) && is_array($h) && (isset($h['IPAddress']['_value']) || isset($h['MACAddress']['_value'])))
                    ->map(function ($h) {
                        $iface = strtolower((string) ($h['InterfaceType']['_value'] ?? $h['Layer2Interface']['_value'] ?? ''));
                        $active = $h['Active']['_value'] ?? true;

                        return [
                            'name' => trim((string) ($h['HostName']['_value'] ?? '')) ?: null,
                            'ip' => $h['IPAddress']['_value'] ?? null,
                            'mac' => $h['MACAddress']['_value'] ?? null,
                            'via' => str_contains($iface, '802.11') || str_contains($iface, 'wlan') || str_contains($iface, 'wifi') ? 'Wi-Fi'
                                : (str_contains($iface, 'ethernet') || str_contains($iface, 'lan') ? 'LAN' : null),
                            'active' => in_array($active, [true, 1, '1', 'true'], true),
                        ];
                    })->values();
                $hostsVia = $hosts->contains(fn ($h) => $h['via'] !== null);   // kolom "Lewat" hanya bila modem melaporkannya
                $optic = [
                    ['Daya Rx', $rxPower !== null ? number_format($rxPower, 2) . ' dBm' : null, $rxPower === null ? '' : ($rxPower < -27 ? 'text-rose-600' : ($rxPower < -24 ? 'text-amber-600' : 'text-emerald-600 dark:text-emerald-400'))],
                    ['Daya Tx', $txPower !== null ? number_format($txPower, 2) . ' dBm' : null, ''],
                    ['Suhu transceiver', $temperature !== null ? number_format($temperature, 1) . ' °C' : null, $temperature !== null && $temperature >= 70 ? 'text-rose-600' : ''],
                    ['Arus bias laser', $bias !== null ? number_format($bias, 2) . ' mA' : null, ''],
                ];
            @endphp
            <div id="tab-diagnostics" class="tab-content space-y-6 hidden">
                <section class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Optik (GPON/EPON)</h3>
                        <span class="text-xs text-surface-500">Rx: hijau ≥ -24 · kuning -24 s/d -27 · merah &lt; -27 dBm</span>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-surface-100 dark:divide-surface-800">
                        @foreach($optic as [$label, $value, $cls])
                            <div class="p-5">
                                <p class="text-xs font-medium text-surface-500">{{ $label }}</p>
                                <p class="mt-1 text-xl font-semibold tabular-nums {{ $value === null ? 'text-surface-300 dark:text-surface-600' : ($cls ?: 'text-surface-900 dark:text-white') }}">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <section class="card xl:col-span-2 overflow-hidden">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">Perangkat terhubung</h3>
                            <span class="badge-primary">{{ $hosts->count() }} perangkat</span>
                        </div>
                        @if($hosts->isNotEmpty())
                            <div class="max-h-[26rem] overflow-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-surface-50 dark:bg-surface-900 text-left text-xs font-medium uppercase tracking-wide text-surface-500">
                                        <tr><th class="px-5 py-2.5">Nama</th><th class="px-5 py-2.5">IP</th><th class="px-5 py-2.5">MAC</th>@if($hostsVia)<th class="px-5 py-2.5">Lewat</th>@endif</tr>
                                    </thead>
                                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                                        @foreach($hosts as $h)
                                            <tr class="{{ $h['active'] ? '' : 'opacity-50' }}">
                                                <td class="px-5 py-2.5">
                                                    <span class="inline-flex items-center gap-2">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $h['active'] ? 'bg-emerald-500' : 'bg-surface-300' }}"></span>
                                                        <span class="{{ $h['name'] ? 'font-medium text-surface-900 dark:text-white' : 'italic text-surface-400' }}">{{ $h['name'] ?? 'Tanpa nama' }}</span>
                                                    </span>
                                                </td>
                                                <td class="px-5 py-2.5 font-mono text-xs text-surface-700 dark:text-surface-300">{{ $h['ip'] ?? '—' }}</td>
                                                <td class="px-5 py-2.5 font-mono text-xs text-surface-500">{{ $h['mac'] ?? '—' }}</td>
                                                @if($hostsVia)<td class="px-5 py-2.5">@if($h['via'])<span class="badge-gray">{{ $h['via'] }}</span>@else<span class="text-surface-400">—</span>@endif</td>@endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="card-body text-sm text-surface-500">Modem belum melaporkan perangkat yang terhubung. Klik <strong>Sinkron data</strong> untuk membaca ulang.</p>
                        @endif
                    </section>

                    <section class="card">
                        <div class="card-header"><h3 class="card-title">Antrean perintah</h3></div>
                        <div class="card-body space-y-2">
                            @forelse($tasks ?? [] as $task)
                                @php
                                    $taskFault = ($faults ?? [])[(string) ($task['_id'] ?? '')] ?? null;
                                    $taskLabel = ['setParameterValues' => 'Ubah pengaturan', 'reboot' => 'Reboot', 'refreshObject' => 'Sinkron data', 'factoryReset' => 'Factory reset', 'getParameterValues' => 'Baca parameter', 'addObject' => 'Tambah objek', 'deleteObject' => 'Hapus objek', 'download' => 'Unduh firmware'][$task['name']] ?? $task['name'];
                                @endphp
                                <div class="rounded-lg border px-3 py-2 {{ $taskFault ? 'border-rose-200 bg-rose-50 dark:border-rose-500/20 dark:bg-rose-500/10' : 'border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10' }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium {{ $taskFault ? 'text-rose-900 dark:text-rose-200' : 'text-amber-900 dark:text-amber-200' }}">{{ $taskLabel }}</p>
                                            <p class="text-xs {{ $taskFault ? 'text-rose-700/80 dark:text-rose-300/80' : 'text-amber-700/80 dark:text-amber-300/70' }}">dikirim {{ \Carbon\Carbon::parse($task['timestamp'])->locale('id')->diffForHumans() }}</p>
                                        </div>
                                        <span class="{{ $taskFault ? 'badge-danger' : 'badge-warning' }}">{{ $taskFault ? 'Gagal' : 'Menunggu' }}</span>
                                    </div>
                                    @if($taskFault)
                                        <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">{{ $taskFault }}</p>
                                        <form method="POST" action="{{ route('acs-servers.delete-task', ['deviceId' => $deviceId, 'taskId' => $task['_id']]) }}" class="mt-2"
                                              onsubmit="return confirm('Hapus perintah yang gagal ini dari antrean modem?')">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                                            <button class="text-xs font-medium text-rose-700 underline-offset-2 hover:underline dark:text-rose-300">Hapus perintah</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="py-6 text-center">
                                    <x-icon name="check-circle" class="mx-auto mb-2 w-8 h-8 text-emerald-500" />
                                    <p class="text-sm text-surface-500">Tidak ada antrean — modem sudah sinkron.</p>
                                </div>
                            @endforelse
                            <p class="pt-1 text-xs text-surface-400">"Menunggu" = modem belum menjalankannya (akan dijalankan saat modem menghubungi GenieACS). "Gagal" = modem menolak; perintah gagal menahan perintah lain untuk modem ini, jadi hapus lalu kirim ulang.</p>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Tab 3: WAN / PPPoE --}}
            <div id="tab-wan" class="tab-content space-y-6 hidden">
                @php
                    $wanList = $wanConnections ?? [];
                    $activePppConn = collect($wanList)->firstWhere('is_active_ppp', true) ?? ($wanList[0] ?? null);
                    $detectedServices = collect($wanList)->pluck('service_list')->filter()->values()->all();
                    $commonServices = array_unique(array_merge($detectedServices, ['INTERNET', 'TR069,INTERNET', 'TR069', 'VOIP', 'IPTV', 'OTHER', 'VOIP,INTERNET', 'IPTV,INTERNET']));
                @endphp

                <section class="card overflow-hidden">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Koneksi WAN di modem</h3>
                        <span class="text-xs text-surface-500">{{ count($wanList) }} koneksi</span>
                    </div>
                    @if(! empty($wanList))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-surface-50 dark:bg-surface-900/60 text-left text-xs font-medium uppercase tracking-wide text-surface-500">
                                    <tr><th class="px-5 py-2.5">Koneksi</th><th class="px-5 py-2.5">Username</th><th class="px-5 py-2.5">VLAN</th><th class="px-5 py-2.5">IP</th><th class="px-5 py-2.5">Layanan</th><th class="px-5 py-2.5">Status</th></tr>
                                </thead>
                                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                                    @foreach($wanList as $c)
                                        <tr class="{{ $c['is_active_ppp'] ? 'bg-primary-50/50 dark:bg-primary-500/5' : '' }}">
                                            <td class="px-5 py-2.5">
                                                <p class="font-medium text-surface-900 dark:text-white">{{ $c['name'] }}</p>
                                                <p class="text-xs text-surface-500">{{ $c['protocol'] }}@if($c['is_active_ppp']) · <span class="text-primary-700 dark:text-primary-300">PPPoE utama</span>@endif</p>
                                            </td>
                                            <td class="px-5 py-2.5 font-mono text-xs">{{ $c['username'] ?: '—' }}</td>
                                            <td class="px-5 py-2.5 font-mono text-xs">{{ $c['vlan_id'] ?? 'tanpa tag' }}</td>
                                            <td class="px-5 py-2.5 font-mono text-xs">{{ $c['ip'] ?: '—' }}</td>
                                            <td class="px-5 py-2.5 text-xs">{{ $c['service_list'] ?: '—' }}</td>
                                            <td class="px-5 py-2.5">
                                                @if($c['status'] === 'Connected')
                                                    <span class="badge-success"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Terhubung</span>
                                                @else
                                                    <span class="badge-gray">{{ $c['status'] ?: 'Tidak aktif' }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="card-body text-sm text-surface-500">Belum ada koneksi WAN dari laporan terakhir modem. Konfigurasi baru tetap bisa dikirim di bawah.</p>
                    @endif
                </section>

                <section class="card" x-data="wanConfigForm(@js($wanList), @js($activePppConn['path'] ?? ''))">
                    <div class="card-header">
                        <h3 class="card-title">Ubah PPPoE & VLAN</h3>
                        <p class="text-xs text-surface-500">Dikirim langsung ke modem lewat TR-069 dan tercatat di Log Aktivitas. Internet pelanggan bisa terputus sesaat.</p>
                    </div>

                    <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" @submit="submitForm($event)" class="card-body space-y-6">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $server->id }}">

                        <div x-show="submitSuccess" x-cloak class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <x-icon name="check-circle" class="w-5 h-5 shrink-0" /> <span x-text="submitMessage"></span>
                        </div>
                        <div x-show="submitError" x-cloak class="flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                            <x-icon name="exclamation" class="w-5 h-5 shrink-0" /> <span x-text="submitError"></span>
                        </div>

                        {{-- Koneksi tujuan --}}
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <label class="{{ $lbl }}" for="wan_path">Koneksi WAN yang diubah</label>
                                <select id="wan_path" name="wan_path" x-model="selectedPath" @change="onWanChange()" class="input mt-1">
                                    @foreach($wanList as $c)
                                        <option value="{{ $c['path'] }}">{{ $c['name'] }} — {{ $c['protocol'] }}{{ $c['username'] ? ' · ' . $c['username'] : '' }} (VLAN {{ $c['vlan_id'] ?? '-' }})</option>
                                    @endforeach
                                    @if(empty($wanList))
                                        <option value="">Koneksi WAN bawaan</option>
                                    @endif
                                </select>
                                <p class="{{ $hint }}">Isian di bawah otomatis mengikuti koneksi yang dipilih.
                                    <span x-show="currentConn" x-cloak>Sekarang: <span class="font-mono" x-text="currentConn && currentConn.ip ? currentConn.ip : 'belum ada IP'"></span> (<span x-text="currentConn && currentConn.status === 'Connected' ? 'terhubung' : (currentConn ? currentConn.status : '')"></span>)</span>
                                </p>
                            </div>
                            <div>
                                <label class="{{ $lbl }}" for="is_enabled">Status koneksi</label>
                                <select id="is_enabled" name="is_enabled" x-model="isEnabled" class="input mt-1" :class="isEnabled == '1' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        {{-- Login PPPoE --}}
                        <fieldset class="space-y-3">
                            <legend class="text-xs font-semibold uppercase tracking-wide text-surface-500">Login PPPoE</legend>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="{{ $lbl }}" for="ppp_username">Username</label>
                                    <input id="ppp_username" type="text" name="ppp_username" x-model="pppUsername" class="input mt-1 font-mono" placeholder="mis. 20391582226@nextlink" autocomplete="off">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}" for="ppp_password">Password</label>
                                    <div class="relative mt-1" x-data="{ show: false }">
                                        <input id="ppp_password" :type="show ? 'text' : 'password'" name="ppp_password" x-model="pppPassword" class="input pr-10 font-mono" placeholder="Kosongkan bila tidak diubah" autocomplete="new-password">
                                        <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-surface-700" :aria-label="show ? 'Sembunyikan' : 'Tampilkan'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Jaringan --}}
                        <fieldset class="space-y-3">
                            <legend class="text-xs font-semibold uppercase tracking-wide text-surface-500">Jaringan</legend>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                <div>
                                    <label class="{{ $lbl }}" for="vlan_id">VLAN ID</label>
                                    <input id="vlan_id" type="number" name="vlan_id" x-model="vlanId" class="input mt-1 font-mono" placeholder="mis. 101">
                                    <p class="{{ $hint }}">Dipetakan otomatis sesuai merek (Huawei/ZTE/TR-181).</p>
                                </div>
                                <div>
                                    <label class="{{ $lbl }}" for="connection_trigger">Mode sambung</label>
                                    <select id="connection_trigger" name="connection_trigger" x-model="connectionTrigger" class="input mt-1">
                                        <option value="AlwaysOn">Selalu tersambung (disarankan)</option>
                                        <option value="OnDemand">Saat ada trafik</option>
                                        <option value="Manual">Manual</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $lbl }}" for="mru">MRU / MTU</label>
                                    <input id="mru" type="number" name="mru" x-model="mru" class="input mt-1 font-mono" placeholder="1492">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="{{ $lbl }}" for="service_type">Jenis layanan</label>
                                    <select id="service_type" x-model="serviceType" @change="onServiceSelect($event.target.value)" class="input mt-1">
                                        @foreach($commonServices as $svc)
                                            <option value="{{ $svc }}">{{ $svc }}{{ in_array($svc, $detectedServices) ? ' — ada di modem' : '' }}</option>
                                        @endforeach
                                        <option value="__CUSTOM__">Lainnya (ketik sendiri)…</option>
                                    </select>
                                    <input x-show="isCustomService" x-cloak type="text" name="service_list_custom" x-model="customServiceType" class="input mt-2 font-mono" placeholder="mis. OTHER_VOIP">
                                    <input type="hidden" name="service_list" :value="isCustomService ? customServiceType : serviceType">
                                </div>
                                <div>
                                    <label class="{{ $lbl }}" for="nat_enabled">NAT</label>
                                    <select id="nat_enabled" name="nat_enabled" x-model="natEnabled" class="input mt-1">
                                        <option value="1">Aktif (disarankan)</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Binding port --}}
                        <fieldset class="space-y-3">
                            <legend class="text-xs font-semibold uppercase tracking-wide text-surface-500">Port yang memakai koneksi ini</legend>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="port in [1, 2, 3, 4]" :key="'lan' + port">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                                           :class="lanBind['lan' + port] ? 'border-primary-400 bg-primary-50 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200' : 'border-surface-200 text-surface-600 hover:bg-surface-50 dark:border-surface-700 dark:text-surface-300 dark:hover:bg-surface-800'">
                                        <input type="checkbox" :name="'lan_bind[lan' + port + ']'" value="1" x-model="lanBind['lan' + port]" class="rounded border-surface-300 text-primary-600">
                                        <span x-text="'LAN ' + port"></span>
                                    </label>
                                </template>
                                <span class="mx-1 self-center text-surface-300">|</span>
                                <template x-for="ssid in [1, 2, 3, 4]" :key="'ssid' + ssid">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                                           :class="ssidBind['ssid' + ssid] ? 'border-primary-400 bg-primary-50 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200' : 'border-surface-200 text-surface-600 hover:bg-surface-50 dark:border-surface-700 dark:text-surface-300 dark:hover:bg-surface-800'">
                                        <input type="checkbox" :name="'ssid_bind[ssid' + ssid + ']'" value="1" x-model="ssidBind['ssid' + ssid]" class="rounded border-surface-300 text-primary-600">
                                        <span x-text="'Wi-Fi ' + ssid"></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>

                        <div class="flex flex-col-reverse gap-3 border-t border-surface-200 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-surface-800">
                            <p class="text-xs text-surface-500">Modem menyimpan perubahan lalu dihubungi ulang (connection request).</p>
                            <button type="submit" :disabled="submitting" class="btn-primary">
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="submitting ? 'Mengirim ke modem…' : 'Kirim ke modem'"></span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            {{-- Tab 4: Wi-Fi --}}
            <div id="tab-wireless" class="tab-content hidden">
                @php $wifiCards = 0; @endphp
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @for($i = 1; $i <= 4; $i++)
                        @php
                            $igdWlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$i] ?? null;
                            $devWlanSsid = $device['Device']['WiFi']['SSID'][$i] ?? null;
                            $devWlanRadio = $device['Device']['WiFi']['Radio'][$i] ?? null;
                            $devWlanAp = $device['Device']['WiFi']['AccessPoint'][$i] ?? null;
                            if (! $igdWlan && ! $devWlanSsid) {
                                continue;
                            }
                            $wifiCards++;
                            $ssid = $igdWlan['SSID']['_value'] ?? $devWlanSsid['SSID']['_value'] ?? '';
                            $band = $devWlanRadio['OperatingFrequencyBand']['_value'] ?? ($igdWlan['OperatingFrequencyBand']['_value'] ?? ($i == 2 || $i == 6 ? '5 GHz' : '2.4 GHz'));
                            $wlanEnabled = in_array($igdWlan['Enable']['_value'] ?? $devWlanRadio['Enable']['_value'] ?? null, ['1', 1, true, 'true'], true);
                            $channel = $igdWlan['Channel']['_value'] ?? $devWlanRadio['Channel']['_value'] ?? null;
                            $security = $igdWlan['BeaconType']['_value'] ?? $devWlanAp['Security']['ModeEnabled']['_value'] ?? null;
                            $clients = $igdWlan['TotalAssociations']['_value'] ?? null;
                        @endphp
                        <section class="card">
                            <div class="card-header flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs text-surface-500">Wi-Fi {{ $i }} · {{ str_replace('GHz', ' GHz', str_replace(' ', '', $band)) }}</p>
                                    <h3 class="card-title truncate">{{ $ssid ?: 'Tanpa nama' }}</h3>
                                </div>
                                <span class="{{ $wlanEnabled ? 'badge-success' : 'badge-gray' }}">{{ $wlanEnabled ? 'Aktif' : 'Mati' }}</span>
                            </div>
                            <dl class="grid grid-cols-3 divide-x divide-surface-100 border-b border-surface-100 text-center dark:divide-surface-800 dark:border-surface-800">
                                <div class="p-3"><dt class="text-xs text-surface-500">Channel</dt><dd class="text-sm font-medium text-surface-900 dark:text-white">{{ $channel ?: 'Otomatis' }}</dd></div>
                                <div class="p-3"><dt class="text-xs text-surface-500">Keamanan</dt><dd class="truncate text-sm font-medium text-surface-900 dark:text-white" title="{{ $security }}">{{ $security ?: '—' }}</dd></div>
                                <div class="p-3"><dt class="text-xs text-surface-500">Klien</dt><dd class="text-sm font-medium text-surface-900 dark:text-white">{{ $clients ?? '—' }}</dd></div>
                            </dl>
                            <form action="{{ route('acs-servers.update-config', ['deviceId' => $deviceId]) }}" method="POST" class="card-body space-y-4"
                                  onsubmit="return confirm('Kirim perubahan Wi-Fi {{ $i }} ke modem? Perangkat yang tersambung akan terputus dan perlu sambung ulang.')">
                                @csrf
                                <input type="hidden" name="server_id" value="{{ $server->id }}">
                                <input type="hidden" name="instance" value="{{ $i }}">
                                <div>
                                    <label class="{{ $lbl }}" for="wifi_ssid_{{ $i }}">Nama Wi-Fi (SSID)</label>
                                    <input id="wifi_ssid_{{ $i }}" type="text" name="wifi_ssid" value="{{ $ssid }}" maxlength="32" class="input mt-1">
                                </div>
                                <div x-data="{ show: false }">
                                    <label class="{{ $lbl }}" for="wifi_password_{{ $i }}">Password Wi-Fi baru</label>
                                    <div class="relative mt-1">
                                        <input id="wifi_password_{{ $i }}" :type="show ? 'text' : 'password'" name="wifi_password" minlength="8" maxlength="63" class="input pr-10" placeholder="Kosongkan bila tidak diubah" autocomplete="new-password">
                                        <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-surface-700" :aria-label="show ? 'Sembunyikan' : 'Tampilkan'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                    <p class="{{ $hint }}">Minimal 8 karakter.</p>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="btn-primary"><x-icon name="check" class="w-4 h-4" /> Simpan Wi-Fi {{ $i }}</button>
                                </div>
                            </form>
                        </section>
                    @endfor
                </div>
                @if($wifiCards === 0)
                    <div class="card card-body text-center text-sm text-surface-500">Modem belum melaporkan pengaturan Wi-Fi. Klik <strong>Sinkron data</strong> untuk membaca ulang.</div>
                @endif
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
                    return response.json().catch(function() { return { success: false, message: 'HTTP ' + response.status }; });
                })
                .then(function(data) {
                    showToast(data.message || (data.success ? 'Perintah sinkron terkirim.' : 'Sinkron gagal.'), data.success ? 'success' : 'error');
                    if (data.success) watchSync(serverId);
                })
                .catch(function(error) {
                    console.error(error);
                    showToast('Tidak bisa menghubungi server aplikasi. Periksa koneksi lalu coba lagi.', 'error');
                });
            }

            // Pantau antrean setiap 5 detik (maks. 2 menit): muat ulang saat sinkron selesai, beri tahu bila gagal
            function watchSync(serverId) {
                var url = @js(route('acs-servers.queue', ['deviceId' => $deviceId], false)) + '?server_id=' + encodeURIComponent(serverId);
                var tries = 0;
                var timer = setInterval(function () {
                    tries++;
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (!d.success) return;
                            var refresh = (d.tasks || []).filter(function (t) { return t.name === 'refreshObject'; });
                            var failed = refresh.filter(function (t) { return t.fault; });
                            if (failed.length) {
                                clearInterval(timer);
                                showToast('Sinkron gagal: ' + failed[0].fault, 'error');
                            } else if (!refresh.length) {
                                clearInterval(timer);
                                showToast('Sinkron selesai — memuat data terbaru…', 'success');
                                setTimeout(function () { window.location.reload(); }, 800);
                            }
                        })
                        .catch(function () {});
                    if (tries >= 24) {
                        clearInterval(timer);
                        showToast('Modem belum selesai membaca data. Muat ulang halaman beberapa saat lagi.', 'info');
                    }
                }, 5000);
            }

            @if(session('acs_tab'))
                document.addEventListener('DOMContentLoaded', function () { switchTab(@js(session('acs_tab'))); });
            @endif

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
