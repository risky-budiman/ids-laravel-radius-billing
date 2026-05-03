<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('noc.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-indigo-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                        Optical Signal Monitoring
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pantau kualitas redaman (Optical Power) pelanggan secara real-time via SNMP.</p>
                </div>
            </div>

            <div x-data="{ 
                isRunning: {{ $isRunning ? 'true' : 'false' }},
                syncSignals() {
                    this.isRunning = true;
                    fetch('{{ route('noc.signals') }}?refresh=1', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(() => this.pollStatus());
                },
                pollStatus() {
                    let timer = setInterval(() => {
                        fetch('{{ route('noc.signals') }}', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.isRunning = data.is_running;
                            if (!this.isRunning) {
                                clearInterval(timer);
                                window.location.reload();
                            }
                        });
                    }, 3000);
                }
            }" x-init="if(isRunning) pollStatus()">
                <button 
                    @click="syncSignals()"
                    :disabled="isRunning"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-2xl font-bold transition-all shadow-lg shadow-indigo-200 dark:shadow-none"
                >
                    <template x-if="isRunning">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                    <template x-if="!isRunning">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </template>
                    <span x-text="isRunning ? 'Syncing Signals...' : 'Sync All Signals'"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            @if($customers->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-16 text-center border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="inline-flex p-6 bg-rose-50 dark:bg-rose-900/20 rounded-full mb-6">
                        <svg class="w-16 h-16 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Tidak Ada Data Pelanggan Aktif</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">Daftarkan ONU pelanggan terlebih dahulu agar sistem dapat memantau sinyal optik mereka.</p>
                </div>
            @else
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">OLT Source</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">ONU Index</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">RX Power (OLT)</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($customers as $customer)
                                    @php
                                        $cache = $customer->signalCache;
                                        $rxPower = $cache ? $cache->rx_power : null;
                                        $status = $cache ? $cache->status : 'unknown';
                                        
                                        $colorClass = 'text-emerald-500';
                                        $displayPower = $rxPower ? $rxPower . ' dBm' : 'N/A';
                                        
                                        if ($rxPower === null) $colorClass = 'text-gray-400';
                                        elseif ($rxPower < -27) $colorClass = 'text-rose-500 font-black animate-pulse';
                                        elseif ($rxPower < -24) $colorClass = 'text-amber-500 font-bold';
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-8 py-5">
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white">{{ $customer->name }}</p>
                                                <p class="text-[10px] text-gray-500 font-mono">{{ $customer->username }}</p>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $customer->olt->name }}</span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="font-mono text-xs text-gray-500">{{ $customer->onu_index }}</span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col">
                                                <span class="text-lg font-mono {{ $colorClass }}">{{ $displayPower }}</span>
                                                @if($cache)
                                                    <span class="text-[9px] text-gray-400">Polled: {{ $cache->last_polled_at->diffForHumans() }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            @if($status == 'online')
                                                <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded text-[10px] font-bold">ONLINE</span>
                                            @elseif($status == 'dying-gasp')
                                                <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded text-[10px] font-bold">DYING GASP</span>
                                            @elseif($status == 'los')
                                                <span class="px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded text-[10px] font-bold">LOS (CUT)</span>
                                            @else
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded text-[10px] font-bold uppercase">{{ $status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <a href="{{ route('noc.history', $customer->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                History
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
