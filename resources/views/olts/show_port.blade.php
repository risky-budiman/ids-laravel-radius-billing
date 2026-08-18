<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('olts.show', $olt->id) }}" class="mr-4 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                        Port {{ $port->shelf }}/{{ $port->slot }}/{{ $port->pon_port }}
                    </h2>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">{{ $olt->name }}</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button @click="syncLiveSnmp()" :disabled="loading" class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30 flex items-center disabled:opacity-50">
                    <svg class="w-4 h-4 mr-2" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span x-text="loading ? 'Polling SNMP...' : 'Sync SNMP Data'">Sync SNMP Data</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        showDetail: false, 
        selectedOnu: null,
        onus: @json($onus ?? []),
        loading: false,
        syncLiveSnmp() {
            this.loading = true;
            fetch('{{ route('olts.get-port-data', [$olt->id, $port->id]) }}?fresh=1')
                .then(res => res.json())
                .then(data => {
                    this.onus = data.onus || [];
                })
                .catch(err => console.error('SNMP fetch error:', err))
                .finally(() => {
                    this.loading = false;
                });
        },
        openDetail(onu) {
            this.selectedOnu = onu;
            this.showDetail = true;
        }
    }">
        <!-- Port Summary Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Status</span>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full {{ $port->status == 'active' ? 'bg-green-500' : 'bg-gray-300' }} mr-2"></div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ strtoupper($port->status) }}</span>
                </div>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Registered ONUs</span>
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="onus.length"></span>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Board Type</span>
                <span class="font-bold text-gray-800 dark:text-gray-200">{{ $olt->olt_type }}</span>
            </div>
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <span class="block text-xs text-gray-400 uppercase font-bold mb-1">VLAN Tag</span>
                <span class="font-bold text-gray-800 dark:text-gray-200">100 (Default)</span>
            </div>
        </div>

        <!-- ONU List Table -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 dark:text-white">Registered ONUs on this Port</h3>
                
                <!-- Loading Indicator -->
                <div class="flex items-center space-x-4">
                    <div x-show="loading" class="flex items-center text-indigo-600 font-bold text-sm animate-pulse">
                        <svg class="animate-spin h-5 w-5 mr-3 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Fetching data via SNMP...</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Index</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Serial Number</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Description</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Type</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Signal</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Last Reason</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Status</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <!-- Table Data (Dynamic via Alpine) -->
                        <template x-for="onu in onus" :key="onu.index">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                <td class="p-4 font-mono text-sm text-gray-600 dark:text-gray-400" x-text="onu.index"></td>
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="onu.sn"></span>
                                </td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400" x-text="onu.name || '-'"></td>
                                <td class="p-4 text-sm font-mono text-gray-600 dark:text-gray-400" x-text="onu.type"></td>
                                <td class="p-4">
                                    <span class="font-bold" 
                                          :class="onu.signal === 'LOST' || onu.signal === 'N/A' ? 'text-gray-400 italic' : (parseFloat(onu.signal) < -27 ? 'text-red-500' : 'text-green-500')"
                                          x-text="onu.signal === 'LOST' || onu.signal === 'N/A' ? 'No Signal' : onu.signal + ' dBm'">
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase" x-text="onu.reason"></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase"
                                          :class="['working', 'ready', 'online'].includes(onu.status.toLowerCase()) 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                          x-text="onu.status">
                                    </span>
                                </td>
                                <td class="p-4">
                                    <button @click="openDetail(onu)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty/Loading State -->
                        <template x-if="onus.length === 0">
                            <tr>
                                <td colspan="8" class="p-12 text-center text-gray-500 dark:text-gray-400 italic">
                                    <template x-if="loading">
                                        <div class="flex flex-col items-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div>
                                            Scanning ONU list in background... Please wait.
                                        </div>
                                    </template>
                                    <template x-if="!loading">
                                        No registered ONUs found on this port.
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ONU Detail Modal (remains the same but updated for onus array) -->
        <div x-show="showDetail" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showDetail = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 bg-indigo-600 flex justify-between items-center text-white">
                        <h3 class="font-bold">ONU Detailed Diagnostics</h3>
                        <button @click="showDetail = false">&times;</button>
                    </div>
                    <div class="p-6">
                        <template x-if="selectedOnu">
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Customer Name</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white" x-text="selectedOnu.name"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Serial Number</span>
                                        <span class="font-bold text-gray-900 dark:text-white" x-text="selectedOnu.sn"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Status</span>
                                        <span class="font-bold uppercase text-xs" :class="['working', 'ready', 'online'].includes(selectedOnu.status.toLowerCase()) ? 'text-green-500' : 'text-red-500'" x-text="selectedOnu.status"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Signal</span>
                                        <span class="font-bold text-gray-900 dark:text-white" x-text="selectedOnu.signal === 'LOST' ? 'LOST' : selectedOnu.signal + ' dBm'"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Last Reason</span>
                                        <span class="font-bold text-gray-900 dark:text-white" x-text="selectedOnu.reason"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
