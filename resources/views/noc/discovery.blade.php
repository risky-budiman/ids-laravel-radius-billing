<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('noc.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-indigo-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                        ONU Auto Discovery
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mencari perangkat modem (ONT) baru di jaringan PON.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($lastRun)
                    <span class="text-xs text-gray-400">Last scan: {{ $lastRun->diffForHumans() }}</span>
                @endif
                <form action="{{ route('noc.discovery') }}" method="GET">
                    <input type="hidden" name="refresh" value="1">
                    <button type="submit" class="px-6 py-2 {{ $isRunning ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-sm font-bold rounded-xl shadow-lg transition-all flex items-center" {{ $isRunning ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2 {{ $isRunning ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        {{ $isRunning ? 'Scanning...' : 'Rescan Network' }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="discoveryHandler()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <template x-if="isRunning">
                <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-2xl p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600 mr-3"></div>
                        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-400">Pemindaian sedang berjalan di latar belakang...</span>
                    </div>
                    <span class="text-xs text-indigo-500">Halaman akan diperbarui otomatis</span>
                </div>
            </template>

            <div x-show="!onus.length && !isRunning" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-16 text-center border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="inline-flex p-6 bg-gray-50 dark:bg-gray-900 rounded-full mb-6">
                        <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Tidak Ada Perangkat Baru</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">Klik 'Rescan Network' untuk mulai mencari perangkat baru di jaringan.</p>
                </div>
            </div>

            <div x-show="onus.length" x-cloak>
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Source OLT</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Position</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Serial Number</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Discovery Mode</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="onu in onus" :key="onu.sn">
                                    <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 mr-3">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                                <span class="font-bold text-gray-900 dark:text-gray-100" x-text="onu.olt_name"></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex items-center space-x-1">
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-300" x-text="onu.shelf"></span>
                                                <span class="text-gray-300">/</span>
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-300" x-text="onu.slot"></span>
                                                <span class="text-gray-300">/</span>
                                                <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded text-xs font-mono text-indigo-600 dark:text-indigo-400 font-bold" x-text="onu.port"></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="font-mono font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-lg" x-text="onu.sn"></span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded uppercase">SNMP Auto-Scan</span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <a :href="'{{ route('customers.create') }}?sn=' + onu.sn + '&olt_id=' + onu.olt_id + '&pos=' + onu.full_index + '&onu_type=' + (onu.type || 'ALL')" class="inline-flex items-center px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-emerald-500/20">
                                                Register
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function discoveryHandler() {
            return {
                onus: @json($discoveredOnus),
                isRunning: @json($isRunning),
                lastRun: '{{ $lastRun ? $lastRun->diffForHumans() : 'Never' }}',
                pollInterval: null,

                init() {
                    if (this.isRunning) {
                        this.startPolling();
                    }
                },

                startPolling() {
                    this.pollInterval = setInterval(() => {
                        fetch('{{ route('noc.discovery') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.onus = data.onus;
                            this.isRunning = data.is_running;
                            this.lastRun = data.last_run;
                            
                            if (!this.isRunning) {
                                clearInterval(this.pollInterval);
                                // Optional: notify user or refresh UI
                            }
                        });
                    }, 5000);
                }
            }
        }
    </script>
</x-app-layout>
