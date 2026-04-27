<x-app-layout>
    <x-slot name="header">
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
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($customers as $customer)
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
                                            @php
                                                $powerVal = (float)str_replace(' dBm', '', $customer->optical_power);
                                                $colorClass = 'text-emerald-500';
                                                if ($powerVal < -27) $colorClass = 'text-rose-500 font-black animate-pulse';
                                                elseif ($powerVal < -24) $colorClass = 'text-amber-500 font-bold';
                                                elseif ($customer->optical_power == 'N/A' || $customer->optical_power == 'Error') $colorClass = 'text-gray-400';
                                            @endphp
                                            <span class="text-lg font-mono {{ $colorClass }}">{{ $customer->optical_power }}</span>
                                        </td>
                                        <td class="px-8 py-5">
                                            @if($customer->optical_power == 'N/A')
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded text-[10px] font-bold">OFFLINE</span>
                                            @elseif($powerVal < -27)
                                                <span class="px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded text-[10px] font-bold">CRITICAL</span>
                                            @else
                                                <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded text-[10px] font-bold">NORMAL</span>
                                            @endif
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
