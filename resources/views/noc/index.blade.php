<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    NOC Center (Network Operations)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Pusat pemantauan infrastruktur jaringan dan perangkat pelanggan secara real-time.
                </p>
            </div>
            <div class="flex gap-3">
                <button class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-indigo-500/20">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh Status
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- NOC Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
                <!-- Online Users -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User Online</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ $stats['online'] }}</h3>
                </div>

                <!-- Offline Users -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl">
                            <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-3.536 5 5 0 015-5m-9.193 9.193a9 9 0 01-2.121-6.364 9 9 0 019-9"></path></svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User Offline</p>
                    <h3 class="text-3xl font-black text-rose-600 dark:text-rose-400 tabular-nums">{{ $stats['offline'] }}</h3>
                </div>

                <!-- Unconfigured -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-2xl">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <a href="{{ route('noc.discovery') }}" class="text-[10px] font-bold text-amber-600 hover:underline uppercase tracking-wider">Discovery</a>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Discovery ONU</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ $stats['unconfigured'] }}</h3>
                </div>

                <!-- Critical Signals -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl text-rose-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinyal Lemah</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ $stats['critical_signals'] }}</h3>
                </div>

                <!-- Tickets -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tiket Gangguan</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ $stats['open_tickets'] }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Monitoring Area -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- OLT Status Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                                Live Infrastructure Monitoring
                            </h3>
                            <span class="text-xs text-gray-500 font-mono">Last Sync: {{ now()->format('H:i:s') }}</span>
                        </div>
                        @if($olts->isEmpty())
                        <div class="p-12 text-center">
                            <div class="inline-flex p-4 bg-gray-50 dark:bg-gray-900 rounded-full mb-4">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">Belum Ada OLT Terdaftar</h4>
                            <p class="text-sm text-gray-500 max-w-xs mx-auto mt-2">Hubungkan OLT Anda via SNMP untuk memantau trafik PON Port dan redaman ONU secara otomatis.</p>
                            <a href="{{ route('olts.create') }}" class="mt-6 inline-block px-6 py-2 bg-gray-900 dark:bg-white dark:text-gray-900 text-white text-sm font-bold rounded-xl hover:opacity-90 transition-opacity">
                                + Daftarkan OLT
                            </a>
                        </div>
                        @else
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($olts as $olt)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 mr-3">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $olt->name }}</h4>
                                                    <p class="text-[10px] text-gray-500 font-mono">{{ $olt->ip_address }}</p>
                                                </div>
                                            </div>
                                            <span class="flex h-2 w-2 relative">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $olt->is_active ? 'bg-emerald-400' : 'bg-gray-400' }} opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 {{ $olt->is_active ? 'bg-emerald-500' : 'bg-gray-500' }}"></span>
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-[10px] uppercase font-bold tracking-wider text-gray-400">
                                            <div class="bg-white dark:bg-gray-800 p-2 rounded-lg text-center">
                                                <span class="block text-gray-900 dark:text-gray-200 text-xs">--</span>
                                                CPU
                                            </div>
                                            <div class="bg-white dark:bg-gray-800 p-2 rounded-lg text-center">
                                                <span class="block text-emerald-500 text-xs">UP</span>
                                                STATUS
                                            </div>
                                            <div class="bg-white dark:bg-gray-800 p-2 rounded-lg text-center">
                                                <span class="block text-gray-900 dark:text-gray-200 text-xs">{{ $olt->pon_ports_count ?? 0 }}</span>
                                                PORTS
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <a href="{{ route('olts.show', $olt->id) }}" class="block w-full py-2 bg-white dark:bg-gray-800 text-center text-[10px] font-bold text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 transition-colors border border-gray-100 dark:border-gray-700">
                                                DETAILS
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right Sidebar: Recent Activity & Discovery -->
                <div class="space-y-8">
                    <!-- New Discovery Sidebar -->
                    <div class="bg-gradient-to-br from-gray-900 to-indigo-950 rounded-3xl p-6 text-white shadow-xl shadow-indigo-500/10">
                        <h3 class="font-bold mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Auto Discovery
                        </h3>
                        <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-center">
                            <p class="text-xs text-indigo-200 mb-4">Mencari ONU baru yang terdeteksi di PON Port OLT...</p>
                            <div class="flex justify-center space-x-1 mb-4">
                                <div class="w-1 h-4 bg-indigo-500 animate-bounce"></div>
                                <div class="w-1 h-4 bg-indigo-400 animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-1 h-4 bg-indigo-300 animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-300">Scanning...</span>
                        </div>
                    </div>

                    <!-- Network Tools -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Technician Tools</h3>
                        <div class="space-y-3">
                            <button class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors group">
                                <div class="flex items-center">
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm mr-3">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ping Test</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                            <button class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors group">
                                <div class="flex items-center">
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm mr-3">
                                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Reboot ONU</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
