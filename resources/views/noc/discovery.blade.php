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
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mencari perangkat modem (ONT) baru yang terdeteksi di jaringan PON.</p>
                </div>
            </div>
            <button onclick="window.location.reload()" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Rescan Network
            </button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            @if(empty($discoveredOnus))
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-16 text-center border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="inline-flex p-6 bg-gray-50 dark:bg-gray-900 rounded-full mb-6">
                        <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Tidak Ada Perangkat Baru</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">Semua perangkat yang terhubung ke OLT sudah teregistrasi atau tidak ada modem baru yang dicolokkan ke port PON.</p>
                </div>
            @else
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Source OLT</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Position (S/S/P)</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Serial Number</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Type/Model</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($discoveredOnus as $onu)
                                    <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 mr-3">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $onu['olt_name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex items-center space-x-1">
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-300">{{ $onu['shelf'] }}</span>
                                                <span class="text-gray-300">/</span>
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-300">{{ $onu['slot'] }}</span>
                                                <span class="text-gray-300">/</span>
                                                <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded text-xs font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $onu['port'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="font-mono font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-lg">{{ $onu['sn'] }}</span>
                                        </td>
                                        <td class="px-8 py-5 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $onu['type'] }}
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <a href="{{ route('customers.create', ['sn' => $onu['sn'], 'olt_id' => $onu['olt_id'], 'pos' => $onu['full_index']]) }}" class="inline-flex items-center px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-emerald-500/20">
                                                Register ONU
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
